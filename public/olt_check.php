<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

function postCmd($cmd) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    
    $len = strlen($cmd);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0\r\n" .
           "Accept: */*\r\n" .
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

// Login
postCmd("set=login&user={$sid}");

$commands = [
    "get=oltponstatus&sysUnit=1",
    "get=onuauthlist&sysUnit=1",
    "get=onuauthlist&sysUnit=1&pon=1",
    "get=searchonu&sysUnit=1",
    "get=info&sysUnit=1",
    "get=onualllist&sysUnit=1",
];

foreach ($commands as $c) {
    echo "========================================\n";
    echo "COMMAND: {$c}\n";
    echo "========================================\n";
    echo postCmd($c) . "\n\n";
}
