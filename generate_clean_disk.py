import os

file_content = """<?php
// DISK CLEANUP & DIAGNOSTIC UTILITY
// Run this directly on your server to find what is taking up 18.8 GB

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Disk Diagnostic & Cleaner</title>
    <style>
        body { background: #0f172a; color: #f8fafc; font-family: monospace; padding: 30px; line-height: 1.6; }
        .danger { color: #ef4444; }
        .warning { color: #f59e0b; }
        .success { color: #10b981; }
        .box { background: rgba(255,255,255,0.05); padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255,255,255,0.1); }
        .btn { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; text-decoration: none; font-weight: bold; }
        .btn:hover { background: #dc2626; }
    </style>
</head>
<body>
    <h1>Sudarshan Fitness - Server Disk Diagnostic</h1>
    
    <div class="box">
        <h3>1. Searching for Massive PHP Error Logs (error_log)</h3>
        <?php
        $root = __DIR__;
        
        if (isset($_GET['delete_logs'])) {
            $deleted = 0;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
            foreach ($iterator as $file) {
                if ($file->getFilename() === 'error_log') {
                    unlink($file->getPathname());
                    $deleted++;
                }
            }
            echo "<span class='success'>Successfully deleted $deleted error_log files!</span><br><br>";
        } else {
            $total_log_size = 0;
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
            foreach ($iterator as $file) {
                if ($file->getFilename() === 'error_log') {
                    $size = filesize($file->getPathname());
                    $total_log_size += $size;
                    if ($size > 1024 * 1024) { // Only show > 1MB
                        echo "- Found: " . $file->getPathname() . " <strong class='danger'>(" . number_format($size / 1024 / 1024, 2) . " MB)</strong><br>";
                    }
                }
            }
            
            if ($total_log_size > 0) {
                echo "<br>Total error_log size: <strong>" . number_format($total_log_size / 1024 / 1024, 2) . " MB</strong><br><br>";
                echo "<a href='?delete_logs=true' class='btn'>Delete All Error Logs Automatically</a>";
            } else {
                echo "<span class='success'>No massive error logs found.</span>";
            }
        }
        ?>
    </div>

    <div class="box">
        <h3>2. Scanning for Large Files (> 50MB)</h3>
        <?php
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
            $large_files = [];
            
            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $size = $file->getSize();
                    if ($size > 50 * 1024 * 1024) { // 50MB
                        $large_files[$file->getPathname()] = $size;
                    }
                }
            }
            
            arsort($large_files);
            
            if (empty($large_files)) {
                echo "<span class='success'>No single file larger than 50MB found in your web directory.</span>";
            } else {
                foreach ($large_files as $path => $size) {
                    echo "- $path <strong class='danger'>(" . number_format($size / 1024 / 1024 / 1024, 2) . " GB)</strong><br>";
                }
                echo "<br><span class='warning'>⚠️ If you see massive .zip backups or .sql files here, delete them from Hostinger File Manager!</span>";
            }
        } catch(Exception $e) {
            echo "Scan failed: " . $e->getMessage();
        }
        ?>
    </div>
    
    <div class="box">
        <h3>3. What else takes up 18.8 GB?</h3>
        <ul>
            <li><strong>Hostinger Backups:</strong> Old website backups inside the control panel.</li>
            <li><strong>Git History:</strong> A massive hidden <code>.git</code> folder. (Run <code style="color:#38bdf8">rm -rf .git</code> on server).</li>
            <li><strong>Database:</strong> The MySQL database is massive (check phpMyAdmin).</li>
            <li><strong>Emails:</strong> If you use Hostinger Emails (admin@sudarshanfitness.de), the inbox/attachments consume disk space!</li>
        </ul>
    </div>
</body>
</html>
"""

filepath = "./Files/clean_disk.php"
with open(filepath, "w", encoding="utf-8") as f:
    f.write(file_content)
print(f"Created {filepath}")
