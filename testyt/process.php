<?php
set_time_limit(0);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Simple debug log function
function debug_log($msg) {
    file_put_contents(__DIR__ . '/debug.log', date('[Y-m-d H:i:s] ') . $msg . "\n", FILE_APPEND);
}

$ytUrl = $_POST['youtube_url'] ?? null;
if (!$ytUrl) {
    http_response_code(400);
    echo json_encode(["error" => "Missing URL"]);
    exit;
}

// Generate unique ID
$videoId = uniqid('vid_');
$videoDir = __DIR__ . "/videos/$videoId";
$clipsDir = __DIR__ . "/clips/$videoId";

// Create directories and check
if (!mkdir($videoDir, 0777, true) || !mkdir($clipsDir, 0777, true)) {
    http_response_code(500);
    $err = "Failed to create directories";
    debug_log($err);
    echo json_encode(["error" => $err]);
    exit;
}

$videoPath = "$videoDir/original.mp4";

// Download video
$cmdDownload = "yt-dlp -f bestvideo+bestaudio --merge-output-format mp4 -o " . escapeshellarg($videoPath) . " " . escapeshellarg($ytUrl);
exec($cmdDownload, $output, $status);

if ($status !== 0) {
    http_response_code(500);
    $err = "Download failed. yt-dlp output: " . implode("\n", $output);
    debug_log($err);
    echo json_encode(["error" => $err]);
    exit;
}
debug_log("Download success");

// Get video duration
$cmdDuration = "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($videoPath);
$durationStr = shell_exec($cmdDuration);
$duration = (int) round(floatval($durationStr));
debug_log("Video duration: $duration seconds");

if ($duration <= 0) {
    http_response_code(500);
    $err = "Invalid video duration: $durationStr";
    debug_log($err);
    echo json_encode(["error" => $err]);
    exit;
}

// Create 30s clips
$clipLength = 30;
$clips = [];
for ($start = 0, $i = 1; $start < $duration; $start += $clipLength, $i++) {
    $clipFile = "$clipsDir/clip_{$i}.mp4";
    $cmdClip = "ffmpeg -y -i " . escapeshellarg($videoPath) . " -ss $start -t $clipLength -c copy " . escapeshellarg($clipFile);
    exec($cmdClip, $clipOutput, $clipStatus);

    if ($clipStatus !== 0) {
        http_response_code(500);
        $err = "Clip creation failed at clip $i. ffmpeg output: " . implode("\n", $clipOutput);
        debug_log($err);
        echo json_encode(["error" => $err]);
        exit;
    }

    // Confirm clip file created and not empty
    if (!file_exists($clipFile) || filesize($clipFile) === 0) {
        http_response_code(500);
        $err = "Clip file missing or empty at clip $i";
        debug_log($err);
        echo json_encode(["error" => $err]);
        exit;
    }

    $relativePath = "clips/$videoId/clip_{$i}.mp4";
    $clips["clip_$i"] = $relativePath;
}

debug_log("All clips created successfully");

// Return clips JSON
echo json_encode(["clips" => $clips]);
exit;
