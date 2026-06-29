<?php
$files = glob("Files/dashboard/*/*.php");
$files[] = "Files/dashboard/admin/index.php"; // just in case
$files = array_unique($files);

$target = '<img src="../../images/logo.png" alt="" width="192" height="80" />';
$replacement = '<?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />';

foreach ($files as $file) {
    if (is_file($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $target) !== false) {
            $content = str_replace($target, $replacement, $content);
            file_put_contents($file, $content);
            echo "Fixed $file\n";
        }
    }
}
?>
