<?php
$dir = __DIR__;
$scriptPath = __FILE__;

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);

foreach ($iterator as $file) {
    $path = $file->getRealPath();

    if ($file->isDir()) {
        @rmdir($path);
        continue;
    }

    if ($path !== $scriptPath) {
        @unlink($path);
    }
}

register_shutdown_function(function () use ($dir, $scriptPath) {
    if (file_exists($scriptPath)) {
        @unlink($scriptPath);
    }
    @rmdir($dir);
});

header('Location: ../index.php');
exit();
?>

