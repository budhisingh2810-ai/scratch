<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        !isset($_SESSION['one_time_token']) ||
        !isset($_SESSION['allowed_page']) ||
        $_SESSION['allowed_page'] !== 'Bang'
    ) {
        echo '<!DOCTYPE html><html><body></body></html>';
        exit;
    }
}

// 👇 IMPORTANT: HTML file load karo
include 'index.html';
?>