<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

class Attachment extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'attachments';

    protected $fillable = [
        'file_name',
        'disk',
        'path',
        'visibility',
        'mime_type',
        'file_size',
        'ref_type',
        'ref_id',
        'user_id',
        'business_id',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = [
        'download_url',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            $attachment->deleteStoredFile();
        });
    }

    public function getDownloadUrlAttribute(): string
    {
        return $this->downloadUrl();
    }

    public function downloadUrl(): string
    {
        if ($this->isLegacyPublicPath()) {
            return asset(ltrim($this->getRawOriginal('path'), '/'));
        }

        if ($this->usesTemporaryCloudUrl()) {
            return $this->temporaryCloudUrl()
                ?: ($this->resolvedVisibility() === 'public'
                    ? $this->directUrl()
                    : $this->applicationDownloadUrl());
        }

        if ($this->resolvedVisibility() !== 'public') {
            return $this->applicationDownloadUrl();
        }

        return $this->directUrl();
    }

    public function directUrl(): string
    {
        return Storage::disk($this->resolvedDisk())->url($this->getRawOriginal('path'));
    }

    public function applicationDownloadUrl(): string
    {
        return URL::temporarySignedRoute(
            'attachments.download',
            now()->addMinutes($this->signedUrlTtl()),
            ['attachment' => $this->getKey()],
            absolute: false
        );
    }

    public function temporaryCloudUrl(): ?string
    {
        try {
            return Storage::disk($this->resolvedDisk())->temporaryUrl(
                $this->getRawOriginal('path'),
                now()->addMinutes($this->signedUrlTtl())
            );
        } catch (Throwable) {
            return null;
        }
    }

    public function shouldUseApplicationDownload(): bool
    {
        return !$this->usesTemporaryCloudUrl()
            && $this->resolvedVisibility() !== 'public';
    }

    public function usesTemporaryCloudUrl(): bool
    {
        return in_array($this->resolvedDisk(), ['s3', 'gcs'], true);
    }

    public function deleteStoredFile(): void
    {
        try {
            if ($this->isLegacyPublicPath()) {
                $legacyPath = public_path(ltrim($this->getRawOriginal('path'), '/'));
                if (is_file($legacyPath)) {
                    @unlink($legacyPath);
                }

                return;
            }

            $path = $this->getRawOriginal('path');
            if ($path) {
                Storage::disk($this->resolvedDisk())->delete($path);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function isLegacyPublicPath(): bool
    {
        return str_starts_with($this->getRawOriginal('path') ?? '', '/uploads/');
    }

    public function resolvedDisk(): string
    {
        if ($this->disk) {
            return $this->disk;
        }

        return $this->isLegacyPublicPath() ? 'public' : config('filesystems.default_upload_disk', 'public');
    }

    public function resolvedVisibility(): string
    {
        if ($this->visibility) {
            return $this->visibility;
        }

        return 'public';
    }

    protected function signedUrlTtl(): int
    {
        $setting = StorageSetting::query()
            ->when($this->business_id, fn($query) => $query->where('business_id', $this->business_id))
            ->when(!$this->business_id && $this->user_id, fn($query) => $query->whereNull('business_id')->where('user_id', $this->user_id))
            ->first();

        if (!$setting) {
            $setting = StorageSetting::query()
                ->whereNull('business_id')
                ->whereNull('user_id')
                ->latest('id')
                ->first();
        }

        if (!$setting) {
            $setting = StorageSetting::query()
                ->whereNull('business_id')
                ->whereHas('user', fn($query) => $query->where('user_type', 'admin'))
                ->latest('id')
                ->first();
        }

        return $setting?->signed_url_ttl ?: 10;
    }
}
