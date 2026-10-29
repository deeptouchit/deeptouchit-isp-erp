<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

function postCmd($cmd, $referer = 'http://103.59.177.137:8084/') {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    
    $len = strlen($cmd);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
           "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
           "Content-Type: uni_mars_ap\r\n" .
           "Referer: {$referer}\r\n" .
           "Content-Length: {$len}\r\n" .
           "Connection: close\r\n\r\n" .
           $cmd;
            
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    return $res;
}

// 1. Login
$login = postCmd("set=login&user={$sid}");
echo "LOGIN:\n{$login}\n\n";

// 2. Try sysinfo right after login with referer http://103.59.177.137:8084/
$sysinfo = postCmd("get=sysinfo2&sysunit=1");
echo "SYSINFO:\n{$sysinfo}\n\n";

// 3. Try other commands
$tests = [
    "get=sysinfo&sysUnit=1",
    "get=sysinfo2&sysUnit=1",
    "get=poninfo&sysUnit=1",
    "get=pon_info&sysUnit=1",
    "get=ponlist&sysUnit=1",
    "get=portinfo&sysUnit=1",
    "get=onualllist&sysUnit=1",
    "get=onualllist&sysunit=1",
    "get=onulist&sysUnit=1",
    "get=onulist&pon=1",
    "get=onulist&sysunit=1&port=1",
    "get=onulist&sysUnit=1&port=1",
    "get=onulist&sysUnit=1&port=2",
];

foreach ($tests as $t) {
    $res = postCmd($t);
    echo "=== CMD: {$t} ===\n" . substr($res, 0, 400) . "\n\n";
}
