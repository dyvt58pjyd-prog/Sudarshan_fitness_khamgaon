<?php
require '../include/db_conn.php';
$gym = get_gym_details($con);

$campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;
$preview_title = isset($_GET['preview_title']) ? $_GET['preview_title'] : 'Happy Festival!';

$title = $preview_title;
if ($campaign_id > 0) {
    $res = mysqli_query($con, "SELECT campaign_name FROM festival_campaigns WHERE id = $campaign_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $title = $row['campaign_name'];
    }
}

$bg_path = '../images/poster_bg.png';
// Normalize logo path
$raw_logo = $gym['gym_logo'];
if (strpos($raw_logo, '../../') === 0) {
    $logo_path = '../' . substr($raw_logo, 6);
} else {
    $logo_path = '../' . $raw_logo;
}

if (!file_exists($bg_path)) {
    die("Background image missing.");
}

// Create base image
$image = imagecreatefrompng($bg_path);
$width = imagesx($image);
$height = imagesy($image);

// Colors
$white = imagecolorallocate($image, 255, 255, 255);
$orange = imagecolorallocate($image, 255, 107, 0); // Primary accent
$light_gray = imagecolorallocate($image, 200, 200, 200);

// Auto-download fonts if missing (prevents git large file issues)
$font_dir = '../fonts';
if (!is_dir($font_dir)) @mkdir($font_dir, 0777, true);
$font_bold = $font_dir . '/Montserrat-Bold.ttf';
$font_reg = $font_dir . '/Montserrat-Regular.ttf';

function downloadFont($url, $path) {
    if (file_exists($path) && filesize($path) > 1000) return true;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    curl_close($ch);
    if ($data) @file_put_contents($path, $data);
    return file_exists($path);
}

$has_fonts = (downloadFont('https://github.com/JulietaUla/Montserrat/raw/master/fonts/ttf/Montserrat-Bold.ttf', $font_bold) && 
              downloadFont('https://github.com/JulietaUla/Montserrat/raw/master/fonts/ttf/Montserrat-Regular.ttf', $font_reg));

// 1. Draw Gym Name
$gym_name = strtoupper($gym['gym_name']);
if ($has_fonts) {
    $bbox = imagettfbbox(40, 0, $font_bold, $gym_name);
    $x = ($width - ($bbox[2] - $bbox[0])) / 2;
    imagettftext($image, 40, 0, $x, 150, $white, $font_bold, $gym_name);
} else {
    imagestring($image, 5, 400, 150, $gym_name, $white);
}

// 2. Draw Festival Title
if ($has_fonts) {
    $bbox_title = imagettfbbox(75, 0, $font_bold, $title);
    $x_title = ($width - ($bbox_title[2] - $bbox_title[0])) / 2;
    imagettftext($image, 75, 0, $x_title, 500, $orange, $font_bold, $title);
} else {
    imagestring($image, 5, 400, 500, $title, $orange);
}

// 3. Draw Subtitle
$subtitle = "Special Offers & Celebrations!";
if ($has_fonts) {
    $bbox_sub = imagettfbbox(30, 0, $font_reg, $subtitle);
    $x_sub = ($width - ($bbox_sub[2] - $bbox_sub[0])) / 2;
    imagettftext($image, 30, 0, $x_sub, 600, $white, $font_reg, $subtitle);
} else {
    imagestring($image, 4, 400, 600, $subtitle, $white);
}

// 4. Draw Address at bottom
$address = $gym['gym_address'];
if ($has_fonts) {
    $bbox_addr = imagettfbbox(20, 0, $font_reg, $address);
    $x_addr = ($width - ($bbox_addr[2] - $bbox_addr[0])) / 2;
    imagettftext($image, 20, 0, $x_addr, 1000, $light_gray, $font_reg, $address);
} else {
    imagestring($image, 3, 400, 1000, $address, $light_gray);
}

// 5. Overlay Logo if exists
if (file_exists($logo_path)) {
    $ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
    if ($ext == 'png') {
        $logo = @imagecreatefrompng($logo_path);
    } elseif ($ext == 'jpg' || $ext == 'jpeg') {
        $logo = @imagecreatefromjpeg($logo_path);
    }
    
    if (isset($logo) && $logo !== false) {
        $lw = imagesx($logo);
        $lh = imagesy($logo);
        $new_lw = 150;
        $new_lh = ($lh / $lw) * $new_lw;
        
        $lx = ($width - $new_lw) / 2;
        $ly = 200; // Below gym name
        
        imagecopyresampled($image, $logo, $lx, $ly, 0, 0, $new_lw, $new_lh, $lw, $lh);
        imagedestroy($logo);
    }
}

// Output
header('Content-Type: image/png');
// If saving flag is passed, save it instead of rendering
if (isset($_GET['save_path'])) {
    imagepng($image, $_GET['save_path']);
} else {
    imagepng($image);
}
imagedestroy($image);
?>
