<?php
// test_yt-dlp.php

// Make sure this folder exists and is writable
$savePath = "videos/test_video.mp4";
$url = "https://www.youtube.com/watch?v=dQw4w9WgXcQ"; // test URL

exec("yt-dlp -f best -o " . escapeshellarg($savePath) . " " . escapeshellarg($url) . " 2>&1", $output, $return_var);

echo "Return code: $return_var\n";
echo "Output:\n" . implode("\n", $output);
