<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\StorageSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class StorageController extends Controller
{
    public function __construct(
        protected StorageSettingsService $storageSettings
    ) {
    }

    public function index(Request $request)
    {
        return Inertia::render('Backend/Admin/Settings/Storage', [
            'storageSetting' => $this->storageSettings->resolveSettings($request),
            'scope' => $this->storageSettings->scopeSummary($request),
            'diskOptions' => $this->storageSettings->diskOptions(),
            'visibilityOptions' => $this->storageSettings->allowedVisibilities(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'disk' => 'required|string|in:public,s3,gcs',
            'visibility' => 'required|string|in:public,private',
            'directory' => ['nullable', 'string', 'max:150', 'regex:/^[A-Za-z0-9_\/-]+$/'],
            'signed_url_ttl' => 'nullable|integer|min:1|max:1440',
        ]);

        $this->storageSettings->saveForRequest($validated, $request);

        return back()->with('success', 'Storage settings saved successfully.');
    }

    public function update(Request $request, string $id)
    {
        return $this->store($request);
    }

    public function testConnection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'disk' => 'required|string|in:public,s3,gcs',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'The selected disk is invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return response()->json(
            $this->storageSettings->testConnection($request->string('disk')->value(), $request)
        );
    }

    public function download(Request $request, Attachment $attachment)
    {
        abort_unless($request->hasValidSignature(false), 401);

        if ($attachment->isLegacyPublicPath()) {
            return redirect($attachment->downloadUrl());
        }

        if ($attachment->usesTemporaryCloudUrl()) {
            if ($temporaryUrl = $attachment->temporaryCloudUrl()) {
                return redirect()->away($temporaryUrl);
            }
        }

        if ($attachment->resolvedVisibility() === 'public') {
            return redirect($attachment->directUrl());
        }

        return Storage::disk($attachment->resolvedDisk())->download(
            $attachment->getRawOriginal('path'),
            $attachment->file_name
        );
    }
}
