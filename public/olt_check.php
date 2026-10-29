<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

function postCmd($cmd) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED: $errstr";
    
    $len = strlen($cmd);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
           "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
           "Content-Type: uni_mars_ap\r\n" .
           "Referer: http://103.59.177.137:8084/index.html\r\n" .
           "Content-Length: {$len}\r\n" .
           "Connection: close\r\n\r\n" .
           $cmd;
            
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    return $res;
}

// Login first
$l = postCmd("set=login&user={$sid}");
echo "LOGIN: " . (str_contains($l, 'value="0"') ? 'OK' : 'FAIL') . "\n\n";

$cmds = [
    "get=sysinfo2&sysunit=1",
    "get=onualllist&sysunit=1",
    "get=onualllist&sysUnit=1",
    "get=info&sysunit=1&ponport=1",
    "get=info&sysUnit=1&ponport=1",
    "get=info&sysunit=1&ponport=1&displayfunc=displayAll",
    "get=onuauthlist&sysunit=1",
    "get=onuauthlist&sysUnit=1",
    "get=searchonu&sysunit=1",
    "get=searchonu&sysUnit=1",
    "get=onustatus&sysunit=1",
    "get=onustats&sysunit=1",
    "get=olt_pon_stat&sysunit=1",
];

foreach ($cmds as $c) {
    $res = postCmd($c);
    if (str_contains($res, "\r\n\r\n")) {
        $parts = explode("\r\n\r\n", $res, 2);
        $body = trim($parts[1] ?? '');
    } else {
        $body = trim($res);
    }
    
    echo "CMD: [{$c}]\n";
    echo "BODY (" . strlen($body) . " bytes):\n" . substr($body, 0, 300) . "\n----------------------------------------\n";
}
