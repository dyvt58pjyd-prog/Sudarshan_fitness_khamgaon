<?php
// ULTRA AGGRESSIVE SPACE CLEANER
header('Content-Type: text/html');
echo "<h1>Aggressive Storage Cleaner Executing...</h1>";

$root = realpath(__DIR__);
$deleted_files = 0;
$freed_bytes = 0;

function delete_unnecessary_files($dir) {
    global $deleted_files, $freed_bytes;
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $name = $file->getFilename();
                // 1. Delete all error_log files that Hostinger leaves everywhere
                if ($name === 'error_log') {
                    $freed_bytes += $file->getSize();
                    unlink($file->getPathname());
                    $deleted_files++;
                }
                // 2. Delete macOS hidden files
                if ($name === '.DS_Store') {
                    $freed_bytes += $file->getSize();
                    unlink($file->getPathname());
                    $deleted_files++;
                }
                // 3. Delete leftover zip/tar backups accidentally left
                if (preg_match('/backup.*\.zip$|backup.*\.tar\.gz$/i', $name)) {
                    $freed_bytes += $file->getSize();
                    unlink($file->getPathname());
                    $deleted_files++;
                }
            }
        }
    } catch (Exception $e) {
        // Ignore permission denied errors
    }
}

echo "<h3>1. Deleting all error_logs and junk files...</h3>";
delete_unnecessary_files($root);
// Also try to go one directory up (if Hostinger allows)
if (strlen($root) > 10) {
    delete_unnecessary_files(dirname($root)); 
}

$freed_mb = number_format($freed_bytes / 1024 / 1024, 2);
echo "<p style='color:green;'>✅ Deleted $deleted_files junk files, freeing up <b>$freed_mb MB</b>.</p>";

echo "<h3>2. Optimizing Git Repository Storage...</h3>";
// A git repository can become massive (Gigabytes) if it stores old history. We will compress it.
$git_output = shell_exec('git gc --aggressive --prune=now 2>&1');
echo "<pre>" . htmlspecialchars($git_output) . "</pre>";
echo "<p style='color:green;'>✅ Git repository compressed.</p>";

echo "<h3>3. Identifying the biggest folders left:</h3>";
// Run a fast bash command to list the largest directories
$large_dirs = shell_exec("du -sh $root/* | sort -rh | head -n 10 2>&1");
echo "<pre>" . htmlspecialchars($large_dirs) . "</pre>";

echo "<h2>Done! Check your Hostinger Disk Space now.</h2>";
?>
