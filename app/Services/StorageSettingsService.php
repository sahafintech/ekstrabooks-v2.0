<?php

namespace App\Services;

use App\Models\StorageSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class StorageSettingsService
{
    public const ALLOWED_DISKS = ['public', 's3', 'gcs'];
    public const ALLOWED_VISIBILITIES = ['public', 'private'];
    public const DEFAULT_DIRECTORY = 'attachments';
    public const DEFAULT_SIGNED_URL_TTL = 10;

    public function allowedDisks(): array
    {
        return self::ALLOWED_DISKS;
    }

    public function allowedVisibilities(): array
    {
        return self::ALLOWED_VISIBILITIES;
    }

    public function diskOptions(): array
    {
        return [
            [
                'value' => 'public',
                'label' => 'Local Public',
                'description' => 'Stores files on this application server using Laravel\'s public disk.',
                'configured' => true,
            ],
            [
                'value' => 's3',
                'label' => 'Amazon S3',
                'description' => 'Stores files in an AWS S3 bucket using the configured filesystem disk.',
                'configured' => $this->isS3Configured(),
            ],
            [
                'value' => 'gcs',
                'label' => 'Google Cloud Storage',
                'description' => 'Stores files in a Google Cloud Storage bucket using the configured filesystem disk.',
                'configured' => $this->isGcsConfigured(),
            ],
        ];
    }

    public function resolveSettings(?Request $request = null): array
    {
        [$businessId, $userId] = $this->resolveScope($request);
        $setting = $this->resolveEffectiveSetting($request, $businessId, $userId);

        $disk = $setting?->disk ?: config('filesystems.default_upload_disk', 'public');
        $visibility = $setting?->visibility ?: 'public';

        if ($disk === 'public') {
            $visibility = 'public';
        }

        return [
            'disk' => $disk,
            'visibility' => $visibility,
            'directory' => $setting?->directory ?: self::DEFAULT_DIRECTORY,
            'signed_url_ttl' => $setting?->signed_url_ttl ?: self::DEFAULT_SIGNED_URL_TTL,
            'business_id' => $businessId,
            'user_id' => $userId,
            'storage_setting_id' => $setting?->id,
        ];
    }

    public function saveForRequest(array $validated, ?Request $request = null): StorageSetting
    {
        [$businessId, $userId] = $this->resolveScope($request);
        $user = $request?->user() ?: Auth::user();

        if ($businessId === null && $userId === null && !$this->isGlobalScope($request, $user)) {
            throw new RuntimeException('No authenticated storage scope could be resolved.');
        }

        $payload = [
            'disk' => $validated['disk'],
            'visibility' => $validated['disk'] === 'public' ? 'public' : $validated['visibility'],
            'directory' => $validated['directory'] ?: self::DEFAULT_DIRECTORY,
            'signed_url_ttl' => $validated['signed_url_ttl'] ?? self::DEFAULT_SIGNED_URL_TTL,
        ];

        if ($this->isGlobalScope($request, $user)) {
            return $this->saveGlobalSetting($payload, $user?->id);
        }

        if ($businessId !== null) {
            return StorageSetting::updateOrCreate(
                ['business_id' => $businessId],
                array_merge($payload, ['user_id' => null])
            );
        }

        return StorageSetting::updateOrCreate(
            ['business_id' => null, 'user_id' => $userId],
            $payload
        );
    }

    public function scopeSummary(?Request $request = null): array
    {
        [$businessId, $userId] = $this->resolveScope($request);
        $user = $request?->user() ?: Auth::user();

        if ($businessId !== null) {
            $business = $this->resolveActiveBusiness($request);

            return [
                'type' => 'business',
                'label' => $business?->name ?: 'Current business',
                'business_id' => $businessId,
                'user_id' => null,
            ];
        }

        if ($this->isGlobalScope($request, $user)) {
            return [
                'type' => 'global',
                'label' => 'Global default',
                'business_id' => null,
                'user_id' => null,
            ];
        }

        return [
            'type' => 'user',
            'label' => $user?->name ?: 'Current user',
            'business_id' => null,
            'user_id' => $userId,
        ];
    }

    public function testConnection(string $disk, ?Request $request = null): array
    {
        if (!in_array($disk, self::ALLOWED_DISKS, true)) {
            throw new RuntimeException('The selected disk is not allowed.');
        }

        $settings = $this->resolveSettings($request);
        $visibility = $disk === 'public' ? 'public' : $settings['visibility'];
        $probeDirectory = trim($settings['directory'] ?: self::DEFAULT_DIRECTORY, '/');
        $probePath = $probeDirectory . '/.healthchecks/' . Str::uuid() . '.txt';
        $diskConfig = array_merge(config("filesystems.disks.{$disk}", []), ['throw' => true]);
        $filesystem = Storage::build($diskConfig);
        $options = $disk === 'gcs' ? [] : ['visibility' => $visibility];

        try {
            $written = $filesystem->put($probePath, 'storage-connection-test', $options);

            if ($written === false) {
                throw new RuntimeException('The storage probe file could not be uploaded.');
            }

            $filesystem->delete($probePath);

            return [
                'ok' => true,
                'message' => sprintf('Connection to the [%s] disk succeeded.', $disk),
            ];
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'message' => sprintf('Connection to the [%s] disk failed: %s', $disk, $e->getMessage()),
            ];
        }
    }

    protected function resolveScope(?Request $request = null): array
    {
        $request ??= request();
        $businessId = data_get($this->resolveActiveBusiness($request), 'id');

        if ($businessId) {
            return [(int) $businessId, null];
        }

        $user = $request?->user() ?: Auth::user();
        $userId = $this->isGlobalScope($request, $user) ? null : ($user?->id ?: Auth::id());

        return [$businessId ? (int) $businessId : null, $userId ? (int) $userId : null];
    }

    protected function findSetting(?int $businessId, ?int $userId): ?StorageSetting
    {
        if ($businessId !== null) {
            return StorageSetting::forBusiness($businessId)->first();
        }

        if ($userId !== null) {
            return StorageSetting::forUser($userId)->first();
        }

        return null;
    }

    protected function resolveEffectiveSetting(?Request $request, ?int $businessId, ?int $userId): ?StorageSetting
    {
        if ($setting = $this->findSetting($businessId, $userId)) {
            return $setting;
        }

        if ($setting = $this->findGlobalSetting()) {
            return $setting;
        }

        return $this->findLegacyAdminSetting();
    }

    protected function findGlobalSetting(): ?StorageSetting
    {
        return StorageSetting::query()
            ->whereNull('business_id')
            ->whereNull('user_id')
            ->latest('id')
            ->first();
    }

    protected function findLegacyAdminSetting(): ?StorageSetting
    {
        return StorageSetting::query()
            ->whereNull('business_id')
            ->whereNotNull('user_id')
            ->whereHas('user', fn($query) => $query->where('user_type', 'admin'))
            ->latest('id')
            ->first();
    }

    protected function saveGlobalSetting(array $payload, ?int $adminUserId = null): StorageSetting
    {
        $setting = $this->findGlobalSetting();

        if (!$setting && $adminUserId) {
            $setting = StorageSetting::query()
                ->whereNull('business_id')
                ->where('user_id', $adminUserId)
                ->first();
        }

        $setting ??= new StorageSetting();
        $setting->business_id = null;
        $setting->user_id = null;
        $setting->fill($payload);
        $setting->save();

        return $setting;
    }

    protected function isGlobalScope(?Request $request = null, $user = null): bool
    {
        $request ??= request();
        $user ??= $request?->user() ?: Auth::user();

        return $this->resolveActiveBusiness($request) === null
            && $user?->user_type === 'admin';
    }

    protected function resolveActiveBusiness(?Request $request = null)
    {
        $request ??= request();

        if ($request?->has('activeBusiness')) {
            return $request->activeBusiness;
        }

        return $request?->attributes->get('activeBusiness');
    }

    protected function isS3Configured(): bool
    {
        return filled(config('filesystems.disks.s3.key'))
            && filled(config('filesystems.disks.s3.secret'))
            && filled(config('filesystems.disks.s3.region'))
            && filled(config('filesystems.disks.s3.bucket'));
    }

    protected function isGcsConfigured(): bool
    {
        $disk = config('filesystems.disks.gcs', []);

        return filled(Arr::get($disk, 'project_id'))
            && filled(Arr::get($disk, 'bucket'))
            && filled(Arr::get($disk, 'key_file_path'));
    }
}
