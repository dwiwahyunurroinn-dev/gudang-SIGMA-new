<?php
$dir = __DIR__ . '/assets/uploads/';
echo "<pre style='font-size:15px'>";
echo "Lokasi folder : " . $dir . "\n";
echo "Path asli     : " . (realpath($dir) ?: '(tidak ditemukan)') . "\n";
echo "Folder ada?   : " . (is_dir($dir) ? 'YA' : 'TIDAK ADA') . "\n";
echo "Bisa ditulis? : " . (is_writable($dir) ? 'YA' : 'TIDAK') . "\n";
echo "User PHP      : " . (function_exists('posix_geteuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user()) . "\n";
echo "file_uploads  : " . ini_get('file_uploads') . "\n";
echo "upload_max    : " . ini_get('upload_max_filesize') . "\n";
echo "</pre>";