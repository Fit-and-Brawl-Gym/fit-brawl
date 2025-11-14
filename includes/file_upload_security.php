<?php
/**
 * Secure File Upload Helper
 * Provides secure file upload validation and handling
 */
class SecureFileUpload {
    private $allowedMimeTypes;
    private $allowedExtensions;
    private $maxFileSize;
    private $uploadDir;

    /**
     * Constructor
     */
    public function __construct($allowedMimeTypes, $allowedExtensions, $maxFileSize, $uploadDir) {
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->allowedExtensions = $allowedExtensions;
        $this->maxFileSize = $maxFileSize;
        $this->uploadDir = $uploadDir;
    }

    /**
     * Validate and upload a file securely
     */
    public function uploadFile($fileInput) {
        // Check if file was uploaded
        if (!isset($fileInput) || $fileInput['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No file uploaded or upload error occurred'];
        }

        // Check file size
        if ($fileInput['size'] > $this->maxFileSize) {
            return ['success' => false, 'message' => 'File size exceeds maximum allowed size'];
        }

        // Get actual MIME type using finfo
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileInput['tmp_name']);
        finfo_close($finfo);

        // Validate MIME type
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Only specific file types are allowed'];
        }

        // Validate extension
        $extension = strtolower(pathinfo($fileInput['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['success' => false, 'message' => 'Invalid file extension'];
        }

        // Ensure MIME type matches extension
        if (!$this->validateMimeExtensionMatch($mimeType, $extension)) {
            return ['success' => false, 'message' => 'File type does not match its extension'];
        }

        // Create upload directory with secure permissions
        if (!is_dir($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true)) {
                return ['success' => false, 'message' => 'Failed to create upload directory'];
            }
            // Ensure proper ownership in Docker environment
            @chown($this->uploadDir, 'www-data');
            @chgrp($this->uploadDir, 'www-data');
        }

        // Generate secure filename
        $filename = $this->generateSecureFilename($extension);
        $targetPath = $this->uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($fileInput['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file'];
        }

        // Set secure file permissions (more permissive for Docker)
        chmod($targetPath, 0664);
        @chown($targetPath, 'www-data');
        @chgrp($targetPath, 'www-data');

        return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
    }

    /**
     * Validate that MIME type matches extension
     */
    private function validateMimeExtensionMatch($mimeType, $extension) {
        $mimeMap = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/gif' => ['gif'],
            'image/webp' => ['webp'],
            'application/pdf' => ['pdf']
        ];

        return isset($mimeMap[$mimeType]) && in_array($extension, $mimeMap[$mimeType]);
    }

    /**
     * Generate a secure random filename
     */
    private function generateSecureFilename($extension) {
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Helper factory methods for common upload types
     */
    public static function imageUpload($uploadDir, $maxSizeMB = 2) {
        return new self(
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            $maxSizeMB * 1024 * 1024,
            $uploadDir
        );
    }

    public static function receiptUpload($uploadDir, $maxSizeMB = 10) {
        return new self(
            ['image/jpeg', 'image/png', 'application/pdf'],
            ['jpg', 'jpeg', 'png', 'pdf'],
            $maxSizeMB * 1024 * 1024,
            $uploadDir
        );
    }
}
