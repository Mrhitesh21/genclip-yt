<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);


set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL);

$data = json_decode(file_get_contents("php://input"), true);
$clipPath = $data['clip_path'] ?? '';

if (!$clipPath || !file_exists($clipPath)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid clip path"]);
    exit;
}

$original = __DIR__ . '/' . $clipPath;
$pathInfo = pathinfo($clipPath);
$convertedName = $pathInfo['filename'] . '_portrait.mp4';
$convertedPath = __DIR__ . '/' . $pathInfo['dirname'] . '/' . $convertedName;

$cmd = "ffmpeg -y -i \"$original\" -vf \"crop='in_h*9/16:in_h',scale=720:1280\" -preset fast -c:a copy \"$convertedPath\"";
exec($cmd, $output, $status);

if ($status !== 0) {
    http_response_code(500);
    echo json_encode(["error" => "Conversion failed"]);
    exit;
}

echo json_encode(["converted" => $pathInfo['dirname'] . '/' . $convertedName]);
