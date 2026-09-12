<?php
declare(strict_types=1);

$requiredExtensions = ['curl', 'dom', 'fileinfo', 'libxml', 'pdo', 'pdo_sqlite'];
$missing = array_filter($requiredExtensions, static fn (string $extension): bool => !extension_loaded($extension));

if ($missing !== []) {
    fwrite(STDERR, 'Missing PHP extensions: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

$storageDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
if (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {
    fwrite(STDERR, "Storage directory is missing or not writable\n");
    exit(1);
}

$uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content';
if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
    fwrite(STDERR, "Content image upload directory is missing or not writable\n");
    exit(1);
}

echo "PASS: required PHP extensions, storage, and content uploads are available\n";
