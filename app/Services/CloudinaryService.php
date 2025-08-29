<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        Configuration::instance([
            'cloud' => [
                'cloud_name' => config('services.cloudinary.cloud_name'),
                'api_key' => config('services.cloudinary.api_key'),
                'api_secret' => config('services.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => config('services.cloudinary.secure_url', true),
            ],
        ]);

        $this->cloudinary = new Cloudinary();
    }

    /**
     * Upload video recording to Cloudinary
     */
    public function uploadRecording(UploadedFile $file, array $options = []): array
    {
        try {
            Log::info('Starting Cloudinary upload', [
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            $uploadOptions = array_merge([
                'resource_type' => 'video',
                'folder' => 'debate_recordings',
                'use_filename' => true,
                'unique_filename' => true,
                'overwrite' => false,
                'quality' => 'auto',
                'format' => 'mp4',
            ], $options);

            $result = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                $uploadOptions
            );

            Log::info('Cloudinary upload successful', [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url'],
                'resource_type' => $result['resource_type']
            ]);

            return [
                'success' => true,
                'data' => [
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'url' => $result['url'],
                    'format' => $result['format'],
                    'bytes' => $result['bytes'],
                    'duration' => $result['duration'] ?? null,
                    'created_at' => $result['created_at'],
                ]
            ];

        } catch (Exception $e) {
            Log::error('Cloudinary upload failed', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName() ?? null
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Upload recording from URL (for Zoom recordings)
     */
    public function uploadFromUrl(string $url, array $options = []): array
    {
        try {
            Log::info('Starting Cloudinary upload from URL', ['url' => $url]);

            $uploadOptions = array_merge([
                'resource_type' => 'video',
                'folder' => 'debate_recordings',
                'use_filename' => true,
                'unique_filename' => true,
                'overwrite' => false,
                'quality' => 'auto',
                'format' => 'mp4',
            ], $options);

            $result = $this->cloudinary->uploadApi()->upload($url, $uploadOptions);

            Log::info('Cloudinary upload from URL successful', [
                'public_id' => $result['public_id'],
                'secure_url' => $result['secure_url']
            ]);

            return [
                'success' => true,
                'data' => [
                    'public_id' => $result['public_id'],
                    'secure_url' => $result['secure_url'],
                    'url' => $result['url'],
                    'format' => $result['format'],
                    'bytes' => $result['bytes'],
                    'duration' => $result['duration'] ?? null,
                    'created_at' => $result['created_at'],
                ]
            ];

        } catch (Exception $e) {
            Log::error('Cloudinary upload from URL failed', [
                'error' => $e->getMessage(),
                'url' => $url
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Delete recording from Cloudinary
     */
    public function deleteRecording(string $publicId): array
    {
        try {
            $result = $this->cloudinary->uploadApi()->destroy($publicId, [
                'resource_type' => 'video'
            ]);

            Log::info('Recording deleted from Cloudinary', [
                'public_id' => $publicId,
                'result' => $result['result']
            ]);

            return [
                'success' => true,
                'result' => $result['result']
            ];

        } catch (Exception $e) {
            Log::error('Failed to delete recording from Cloudinary', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get recording details from Cloudinary
     */
    public function getRecordingDetails(string $publicId): array
    {
        try {
            $result = $this->cloudinary->adminApi()->asset($publicId, [
                'resource_type' => 'video'
            ]);

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (Exception $e) {
            Log::error('Failed to get recording details', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate signed URL for admin access
     */
    public function generateSignedUrl(string $publicId, int $expiresIn = 3600): array
    {
        try {
            $timestamp = time() + $expiresIn;
            
            $url = $this->cloudinary->utils()->signedUrl($publicId, [
                'resource_type' => 'video',
                'secure' => true,
                'expires_at' => $timestamp,
            ]);

            Log::info('Generated signed URL', [
                'public_id' => $publicId,
                'expires_in' => $expiresIn
            ]);

            return [
                'success' => true,
                'data' => [
                    'url' => $url,
                    'expires_at' => $timestamp
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to generate signed URL', [
                'public_id' => $publicId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Store recording reference in database
     */
    public function storeRecordingReference(int $debateId, array $cloudinaryData, string $type = 'cloudinary_upload'): array
    {
        try {
            $debate = \App\Models\Debate::findOrFail($debateId);

            $debate->update([
                'recording_type' => $type,
                'cloudinary_recording_id' => $cloudinaryData['public_id'],
                'cloudinary_recording_url' => $cloudinaryData['secure_url'],
                'recording_uploaded_at' => now(),
            ]);

            Log::info('Recording reference stored', [
                'debate_id' => $debateId,
                'public_id' => $cloudinaryData['public_id']
            ]);

            return [
                'success' => true,
                'message' => 'Recording reference stored successfully'
            ];

        } catch (Exception $e) {
            Log::error('Failed to store recording reference', [
                'debate_id' => $debateId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}