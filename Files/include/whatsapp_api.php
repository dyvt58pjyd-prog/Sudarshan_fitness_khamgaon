<?php
// whatsapp_api.php

function sendWhatsAppMessage($phone, $message) {
    global $con;
    return enqueue_whatsapp_message($con, $phone, $message);
}
?>
