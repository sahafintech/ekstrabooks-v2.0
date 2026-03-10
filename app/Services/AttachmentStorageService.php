<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class AttachmentStorageService
{
    public function __construct(
        protected StorageSettingsService $storageSettings
    ) {
    }

    public function storeUploadedFile(
        UploadedFile $file,
        string $refType,
        int $refId,
        ?string $fileName = null,
        ?Request $request = null
    ): Attachment {
        $request ??= request();
        $settings = $this->storageSettings->resolveSettings($request);

        $disk = $settings['disk'];
        $visibility = $settings['visibility'];
        $directory = trim($settings['directory'], '/');
        $scopeSegment = $settings['business_id'] !== null
            ? 'business-' . $settings['business_id']
            : 'user-' . $settings['user_id'];
        $dateSegment = now()->format('Y/m');
        $targetDirectory = trim($directory . '/' . $scopeSegment . '/' . $dateSegment, '/');
        $extension = $file->getClientOriginalExtension();
        $storedName = (string) Str::uuid() . ($extension ? '.' . $extension : '');
        $options = $disk === 'gcs' ? [] : ['visibility' => $visibility];

        $path = Storage::disk($disk)->putFileAs(
            $targetDirectory,
            $file,
            $storedName,
            $options
        );

        if (!$path) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        $attachment = new Attachment();
        $attachment->file_name = $fileName ?: $file->getClientOriginalName();
        $attachment->disk = $disk;
        $attachment->path = $path;
        $attachment->visibility = $visibility;
        $attachment->mime_type = $file->getClientMimeType();
        $attachment->file_size = $file->getSize();
        $attachment->ref_type = $refType;
        $attachment->ref_id = $refId;
        $attachment->save();

        return $attachment;
    }
}
