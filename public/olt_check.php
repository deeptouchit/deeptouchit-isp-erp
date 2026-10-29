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

postCmd("set=login&user={$sid}");

echo "=== PON 1 ONU INFO ===\n";
echo postCmd("get=info&sysUnit=1&ponport=1&displayfunc=displayAll") . "\n\n";

echo "=== PON 2 ONU INFO ===\n";
echo postCmd("get=info&sysUnit=1&ponport=2&displayfunc=displayAll") . "\n\n";

echo "=== ONU ALL LIST ===\n";
echo postCmd("get=onualllist&sysUnit=1") . "\n\n";
