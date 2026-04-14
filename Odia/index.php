<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (
        !isset($_SESSION['one_time_token']) ||
        !isset($_SESSION['allowed_page']) ||
        $_SESSION['allowed_page'] !== 'Odia'
    ) {
        echo '<!DOCTYPE html><html><body></body></html>';
        exit;
    }
}

// 👇 main site load
include 'index.html';
?>