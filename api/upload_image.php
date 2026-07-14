<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed.'], 405);
}

// Verify that the file was uploaded
if (!isset($_FILES['image'])) {
    jsonResponse(['success' => false, 'error' => 'No image file uploaded.'], 400);
}

$file = $_FILES['image'];

// Check for PHP upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
        UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    ];
    $errMsg = $errMap[$file['error']] ?? 'Unknown upload error.';
    jsonResponse(['success' => false, 'error' => $errMsg], 400);
}

// Check file size (5MB limit)
$maxSize = 5 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(['success' => false, 'error' => 'File size exceeds the 5MB limit.'], 400);
}

// Validate MIME type
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
];

if (!class_exists('finfo')) {
    // Fallback if finfo is not enabled in php.ini
    $pathInfo = pathinfo($file['name']);
    $extension = strtolower($pathInfo['extension'] ?? '');
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        jsonResponse(['success' => false, 'error' => 'Invalid file extension. Only JPG, PNG, GIF, and WEBP are allowed.'], 400);
    }
} else {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    if (!array_key_exists($mimeType, $allowedTypes)) {
        jsonResponse(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'], 400);
    }
    $extension = $allowedTypes[$mimeType];
}

// Ensure the uploads directory exists
$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        jsonResponse(['success' => false, 'error' => 'Failed to create uploads directory. Please check permissions.'], 500);
    }
}

// Generate a unique filename to prevent overwriting existing files
$fileName = uniqid('item_', true) . '.' . $extension;
$destination = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(['success' => false, 'error' => 'Failed to save the uploaded image.'], 500);
}

// Return the relative URL path
$relativePath = 'uploads/' . $fileName;
jsonResponse(['success' => true, 'image_path' => $relativePath]);
