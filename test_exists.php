<?php
$clean_path = ltrim('../../Sudarshan Data Folder/payment_proof_new_1782833568_3347.jpg', './');
$dir = '/Users/anurag.bawaskar/Downloads/Titan-Gym-master/Files/dashboard/admin';
$physical_path = $dir . '/../../' . $clean_path;
echo "Clean Path: $clean_path\n";
echo "Physical Path: $physical_path\n";
echo "Realpath: " . realpath($physical_path) . "\n";
echo "File Exists: " . (file_exists($physical_path) ? 'YES' : 'NO') . "\n";
?>
