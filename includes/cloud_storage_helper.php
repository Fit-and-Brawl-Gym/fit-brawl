<?php
/**
 * Cloud Storage Helper for Google Cloud Platform
 * Handles file uploads to both local storage and Google Cloud Storage
 */

class CloudStorageHelper {
    private $isGCP;
    private $projectId;
    private $bucketName;
    private $localBasePath;

    public function __construct() {
        // Auto-detect if running on Google Cloud
        $this->isGCP = isset($_SERVER['GAE_ENV']) || isset($_SERVER['GAE_VERSION']);
        $this->projectId = getenv('GCP_PROJECT_ID');
        $this->bucketName = getenv('GCP_STORAGE_BUCKET');
        $this->localBasePath = __DIR__ . '/../uploads/';
    }

    /**
     * Upload file to appropriate storage
     * @param array $fileInput The $_FILES array element
     * @param string $folder Subfolder (avatars, equipment, products, receipts)
     * @param array $allowedTypes Allowed MIME types
     * @param int $maxSize Maximum file size in bytes
     * @return array ['success' => bool, 'filename' => string, 'url' => string, 'message' => string]
     */
    public function uploadFile($fileInput, $folder, $allowedTypes = [], $maxSize = 5242880) {
        // Validate input
        if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
        }

        // Check file size
        if ($fileInput['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
        }

        // Get actual MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileInput['tmp_name']);
        finfo_close($finfo);

        // Validate MIME type
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }

        // Generate secure filename
        $extension = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $filepath = $folder . '/' . $filename;

        // Upload to appropriate storage
        if ($this->isGCP && $this->bucketName) {
            return $this->uploadToGCS($fileInput['tmp_name'], $filepath, $mimeType);
        } else {
            return $this->uploadToLocal($fileInput['tmp_name'], $filepath);
        }
    }

    /**
     * Upload to Google Cloud Storage
     */
    private function uploadToGCS($tmpFile, $filepath, $mimeType) {
        try {
            // Check if Google Cloud Storage library is available
            if (!class_exists('Google\Cloud\Storage\StorageClient')) {
                // Fallback to local if GCS library not available
                return $this->uploadToLocal($tmpFile, $filepath);
            }

            $storage = new \Google\Cloud\Storage\StorageClient([
                'projectId' => $this->projectId
            ]);
            $bucket = $storage->bucket($this->bucketName);

            $object = $bucket->upload(
                fopen($tmpFile, 'r'),
                [
                    'name' => $filepath,
                    'metadata' => [
                        'contentType' => $mimeType
                    ]
                ]
            );

            // Get public URL
            $url = sprintf('https://storage.googleapis.com/%s/%s', $this->bucketName, $filepath);

            return [
                'success' => true,
                'filename' => basename($filepath),
                'path' => $filepath,
                'url' => $url,
                'storage' => 'gcs'
            ];
        } catch (Exception $e) {
            // Fallback to local storage on error
            error_log('GCS upload failed: ' . $e->getMessage());
            return $this->uploadToLocal($tmpFile, $filepath);
        }
    }

    /**
     * Upload to local filesystem
     */
    private function uploadToLocal($tmpFile, $filepath) {
        $fullPath = $this->localBasePath . $filepath;
        $directory = dirname($fullPath);

        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0750, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
        }

        // Move uploaded file
        if (!move_uploaded_file($tmpFile, $fullPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }

        // Set secure file permissions
        chmod($fullPath, 0640);

        // Generate relative URL
        $url = '/uploads/' . $filepath;

        return [
            'success' => true,
            'filename' => basename($filepath),
            'path' => $filepath,
            'url' => $url,
            'storage' => 'local'
        ];
    }

    /**
     * Delete file from storage
     */
    public function deleteFile($filepath) {
        if ($this->isGCP && $this->bucketName) {
            return $this->deleteFromGCS($filepath);
        } else {
            return $this->deleteFromLocal($filepath);
        }
    }

    /**
     * Delete from Google Cloud Storage
     */
    private function deleteFromGCS($filepath) {
        try {
            if (!class_exists('Google\Cloud\Storage\StorageClient')) {
                return $this->deleteFromLocal($filepath);
            }

            $storage = new \Google\Cloud\Storage\StorageClient([
                'projectId' => $this->projectId
            ]);
            $bucket = $storage->bucket($this->bucketName);
            $object = $bucket->object($filepath);
            $object->delete();

            return ['success' => true];
        } catch (Exception $e) {
            error_log('GCS delete failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete from local filesystem
     */
    private function deleteFromLocal($filepath) {
        $fullPath = $this->localBasePath . $filepath;

        if (file_exists($fullPath)) {
            if (unlink($fullPath)) {
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'Failed to delete file'];
            }
        }

        return ['success' => false, 'message' => 'File not found'];
    }

    /**
     * Get file URL
     */
    public function getFileUrl($filepath) {
        if ($this->isGCP && $this->bucketName) {
            return sprintf('https://storage.googleapis.com/%s/%s', $this->bucketName, $filepath);
        } else {
            return '/uploads/' . $filepath;
        }
    }
}
