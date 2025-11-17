<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Nexus\Storage\Contracts\PublicUrlGeneratorInterface;
use Nexus\Storage\Contracts\StorageDriverInterface;
use Nexus\Storage\Exceptions\FileNotFoundException;
use Nexus\Storage\Exceptions\StorageException;
use Nexus\Storage\ValueObjects\Visibility;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * StorageController handles HTTP requests for file storage operations.
 *
 * This controller provides RESTful API endpoints for interacting with
 * the Nexus\Storage package through HTTP.
 *
 * @package App\Http\Controllers
 */
class StorageController
{
    /**
     * Create a new StorageController instance.
     *
     * @param StorageDriverInterface $storageDriver The storage driver
     * @param PublicUrlGeneratorInterface $urlGenerator The URL generator
     */
    public function __construct(
        private readonly StorageDriverInterface $storageDriver,
        private readonly PublicUrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * Upload a file to storage.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'max:' . (config('storage.uploads.max_size') / 1024), // Convert bytes to KB
                'mimetypes:' . implode(',', config('storage.uploads.allowed_mime_types', [])),
            ],
            'path' => ['required', 'string', 'regex:/^[a-zA-Z0-9\/_-]+(\.[a-zA-Z0-9]+)?$/'],
            'visibility' => 'sometimes|in:public,private',
        ]);

        try {
            $file = $request->file('file');
            $path = $request->input('path');
            $visibility = $request->input('visibility', 'private') === 'public'
                ? Visibility::Public
                : Visibility::Private;

            // Get file stream
            $stream = fopen($file->getRealPath(), 'r');
            try {
                $this->storageDriver->put($path, $stream, $visibility);
                $metadata = $this->storageDriver->getMetadata($path);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'path' => $metadata->path,
                    'size' => $metadata->size,
                    'mime_type' => $metadata->mimeType,
                    'formatted_size' => $metadata->getFormattedSize(),
                ],
            ], 201);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload file',
                'error' => $e->getMessage(),
            ], 500);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download a file from storage.
     *
     * @param string $path
     *
     * @return StreamedResponse|JsonResponse
     */
    public function download(string $path): StreamedResponse|JsonResponse
    {
        try {
            $stream = $this->storageDriver->get($path);
            $metadata = $this->storageDriver->getMetadata($path);

            return response()->stream(
                function () use ($stream) {
                    if (is_resource($stream)) {
                        fpassthru($stream);
                        fclose($stream);
                    }
                },
                200,
                [
                    'Content-Type' => $metadata->mimeType,
                    'Content-Disposition' => 'attachment; filename="' . $metadata->getFilename() . '"',
                    'Content-Length' => (string) $metadata->size,
                ]
            );
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to download file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get file metadata.
     *
     * @param string $path
     *
     * @return JsonResponse
     */
    public function metadata(string $path): JsonResponse
    {
        try {
            $metadata = $this->storageDriver->getMetadata($path);

            return response()->json([
                'success' => true,
                'data' => [
                    'path' => $metadata->path,
                    'filename' => $metadata->getFilename(),
                    'directory' => $metadata->getDirectory(),
                    'extension' => $metadata->getExtension(),
                    'size' => $metadata->size,
                    'formatted_size' => $metadata->getFormattedSize(),
                    'mime_type' => $metadata->mimeType,
                    'visibility' => $metadata->visibility->value,
                    'last_modified' => $metadata->lastModified->format('Y-m-d H:i:s'),
                ],
            ]);
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve metadata',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a file exists.
     *
     * @param string $path
     *
     * @return Response
     */
    public function exists(string $path): Response
    {
        try {
            $exists = $this->storageDriver->exists($path);
            return response()->noContent($exists ? 200 : 404);
        } catch (StorageException $e) {
            return response()->noContent(500);
        }
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return JsonResponse
     */
    public function delete(string $path): JsonResponse
    {
        try {
            $this->storageDriver->delete($path);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a temporary URL for a file.
     *
     * @param Request $request
     * @param string $path
     *
     * @return JsonResponse
     */
    public function temporaryUrl(Request $request, string $path): JsonResponse
    {
        $request->validate([
            'expiration' => 'sometimes|integer|min:60|max:' . config('storage.temporary_urls.max_expiration', 86400),
        ]);

        try {
            $expiration = $request->input('expiration', config('storage.temporary_urls.default_expiration', 3600));
            $url = $this->urlGenerator->getTemporaryUrl($path, $expiration);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $url,
                    'expires_in' => $expiration,
                ],
            ]);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate temporary URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List files in a directory.
     *
     * @param Request $request
     * @param string $path
     *
     * @return JsonResponse
     */
    public function listFiles(Request $request, string $path): JsonResponse
    {
        $request->validate([
            'recursive' => 'sometimes|boolean',
        ]);

        try {
            $recursive = $request->boolean('recursive', false);
            $files = $this->storageDriver->listFiles($path, $recursive);

            $filesData = array_map(function ($metadata) {
                return [
                    'path' => $metadata->path,
                    'filename' => $metadata->getFilename(),
                    'size' => $metadata->size,
                    'formatted_size' => $metadata->getFormattedSize(),
                    'mime_type' => $metadata->mimeType,
                    'visibility' => $metadata->visibility->value,
                    'last_modified' => $metadata->lastModified->format('Y-m-d H:i:s'),
                ];
            }, $files);

            return response()->json([
                'success' => true,
                'data' => [
                    'directory' => $path,
                    'files' => $filesData,
                    'count' => count($filesData),
                ],
            ]);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list files',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a directory.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function createDirectory(Request $request): JsonResponse
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        try {
            $path = $request->input('path');
            $this->storageDriver->createDirectory($path);

            return response()->json([
                'success' => true,
                'message' => 'Directory created successfully',
                'data' => [
                    'path' => $path,
                ],
            ], 201);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create directory',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Copy a file.
     *
     * @param Request $request
     * @param string $path
     *
     * @return JsonResponse
     */
    public function copy(Request $request, string $path): JsonResponse
    {
        $request->validate([
            'destination' => 'required|string',
        ]);

        try {
            $destination = $request->input('destination');
            $this->storageDriver->copy($path, $destination);

            return response()->json([
                'success' => true,
                'message' => 'File copied successfully',
                'data' => [
                    'source' => $path,
                    'destination' => $destination,
                ],
            ]);
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Source file not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Move a file.
     *
     * @param Request $request
     * @param string $path
     *
     * @return JsonResponse
     */
    public function move(Request $request, string $path): JsonResponse
    {
        $request->validate([
            'destination' => 'required|string',
        ]);

        try {
            $destination = $request->input('destination');
            $this->storageDriver->move($path, $destination);

            return response()->json([
                'success' => true,
                'message' => 'File moved successfully',
                'data' => [
                    'source' => $path,
                    'destination' => $destination,
                ],
            ]);
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Source file not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to move file',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set file visibility.
     *
     * @param Request $request
     * @param string $path
     *
     * @return JsonResponse
     */
    public function setVisibility(Request $request, string $path): JsonResponse
    {
        $request->validate([
            'visibility' => 'required|in:public,private',
        ]);

        try {
            $visibility = $request->input('visibility') === 'public'
                ? Visibility::Public
                : Visibility::Private;

            $this->storageDriver->setVisibility($path, $visibility);

            return response()->json([
                'success' => true,
                'message' => 'File visibility updated successfully',
                'data' => [
                    'path' => $path,
                    'visibility' => $visibility->value,
                ],
            ]);
        } catch (FileNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
                'error' => $e->getMessage(),
            ], 404);
        } catch (StorageException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update visibility',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
