<?php
session_start();

$map = [
    "hi"  => "Hindi",
    "kn"  => "Kannada",
    "tel" => "Telugu",
    "tm"  => "Tamil",
    "od"  => "Odia",
    "mal" => "Mal",
    "mr"  => "Marathi",
    "bng" => "Bang"
];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));
$slug = strtolower(end($parts));

if (!isset($map[$slug])) {
    echo '<!DOCTYPE html><html><body></body></html>';
    exit;
}

// session create
$_SESSION['one_time_token'] = bin2hex(random_bytes(32));
$_SESSION['allowed_page'] = $map[$slug];

header("Location: /lol/" . $map[$slug] . "/");
exit;
?>