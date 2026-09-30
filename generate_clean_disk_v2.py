import os

file_content = """<?php
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Disk Diagnostic V2</title>
    <style>body { background: #0f172a; color: #f8fafc; font-family: monospace; padding: 30px; }</style>
</head>
<body>
    <h1>Massive File Finder V2</h1>
    <?php
    // Go up multiple levels to reach the absolute root of the Hostinger account (e.g. /home/u123456789/)
    // __DIR__ is something like /home/u123456789/domains/sudarshanfitness.de/public_html/Files
    
    $root = realpath(__DIR__ . '/../../../'); // Attempt to reach /home/u123456789/
    if (!$root || strlen($root) < 3) {
        $root = realpath(__DIR__ . '/../../'); // Fallback to public_html level
    }

    echo "<h3>Scanning Directory: $root</h3>";
    
    if (isset($_GET['delete'])) {
        $file_to_delete = $_GET['delete'];
        // Security check to only delete .tar.gz or .zip backups
        if (strpos($file_to_delete, '.tar.gz') !== false || strpos($file_to_delete, '.zip') !== false || strpos($file_to_delete, 'error_log') !== false) {
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
                echo "<p style='color:#10b981'>Successfully deleted: $file_to_delete</p>";
            }
        }
    }

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CATCH_GET_CHILD
        );
        
        $large_files = [];
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size = $file->getSize();
                if ($size > 50 * 1024 * 1024) { // > 50 MB
                    $large_files[$file->getPathname()] = $size;
                }
            }
        }
        
        arsort($large_files);
        
        if (empty($large_files)) {
            echo "<p style='color:#10b981'>No huge files found even in the root directory!</p>";
        } else {
            foreach ($large_files as $path => $size) {
                $size_gb = number_format($size / 1024 / 1024 / 1024, 2);
                $size_mb = number_format($size / 1024 / 1024, 2);
                echo "<div style='margin-bottom:10px;'>- $path <strong>($size_mb MB / $size_gb GB)</strong>";
                
                // Allow deleting backup files from the UI
                if (strpos($path, '.tar.gz') !== false || strpos($path, '.zip') !== false || strpos($path, 'error_log') !== false) {
                    echo " <a href='?delete=" . urlencode($path) . "' style='color:#ef4444; margin-left:15px; font-weight:bold;'>[DELETE FILE]</a>";
                }
                echo "</div>";
            }
        }
    } catch(Exception $e) {
        echo "Error: " . $e->getMessage();
    }
    ?>
</body>
</html>
"""

filepath = "./Files/clean_disk.php"
with open(filepath, "w", encoding="utf-8") as f:
    f.write(file_content)
print(f"Updated {filepath}")
