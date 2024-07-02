<?php
session_start(); // Ensure session is started. This should be at the very top, before any output.

get_header();

// Initialize $qr_code_url
$qr_code_url = '';

// Check if the UID is set and valid, then retrieve the QR code URL from the session
if (isset($_SESSION['qr_data'])) {
    $qr_code_url = base64_decode($_SESSION['qr_data']);
}

// Custom QR Code Display Logic Here
echo '<div id="qr-code-container">';
// Check if $qr_code_url is not empty before displaying
if (!empty($qr_code_url)) {
    echo '<img src="' . esc_url($qr_code_url) . '" alt="QR Code">';
} else {
    echo '<p>QR Code not found or expired.</p>';
}
echo '</div>';

get_footer();
