<?php

if (!function_exists('logActivity')) {

date_default_timezone_set("Asia/Karachi"); // 🔥 FIX DATE ISSUE

function logActivity($user_id, $role, $activity){

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_name = $_SESSION['name'] ?? "Unknown";
    $time = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    /* DEVICE */
    $device = "Desktop";
    if (preg_match('/mobile/i', $ua)) $device = "Mobile";
    elseif (preg_match('/tablet/i', $ua)) $device = "Tablet";

    /* BROWSER */
    $browser = "Unknown";
    if (strpos($ua, 'Chrome') !== false) $browser = "Chrome";
    elseif (strpos($ua, 'Firefox') !== false) $browser = "Firefox";
    elseif (strpos($ua, 'Safari') !== false) $browser = "Safari";
    elseif (strpos($ua, 'Edge') !== false) $browser = "Edge";

    $message = "[$time] $role | ID:$user_id | $user_name | IP:$ip | Device:$device | Browser:$browser | $activity" . PHP_EOL;

    $folder = __DIR__ . "/../logs/";

    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }

    /* 🔥 DAILY FILE (NOW FIXED DATE) */
    $file = $folder . strtolower($role) . "_" . date("Y-m-d") . ".log";

    file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
}

}
?>