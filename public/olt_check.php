<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

function getPage($path) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    $req = "GET {$path} HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nReferer: http://103.59.177.137:8084/\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    return $res;
}

// First login
$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
$body = "set=login&user={$sid}";
$len = strlen($body);
$req = "POST /sw.cgi HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nContent-Type: uni_mars_ap\r\nContent-Length: {$len}\r\nConnection: close\r\n\r\n{$body}";
fwrite($fp, $req);
while(!feof($fp)) fgets($fp, 1024);
fclose($fp);

$pages = [
    "/index.html",
    "/index.htm",
    "/main.htm",
    "/menu.htm",
    "/left.htm",
    "/top.htm",
    "/m/onu_all_onu.htm",
    "/m/onu_list.htm",
    "/onu_list.htm",
    "/onu_all_onu.htm",
    "/pon_info.htm",
    "/m/pon_info.htm",
];

foreach ($pages as $p) {
    $res = getPage($p);
    $firstLine = strtok($res, "\r\n");
    $len = strlen($res);
    echo "=== {$p} ===: {$firstLine} (Length: {$len})\n";
    if (str_contains($res, '200 OK')) {
        echo substr($res, 0, 600) . "\n\n";
    }
}
