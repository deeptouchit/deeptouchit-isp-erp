<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

function postWithReferer($cmd, $referer) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    
    $len = strlen($cmd);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
           "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
           "X-Requested-With: XMLHttpRequest\r\n" .
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

postWithReferer("set=login&user={$sid}", "http://103.59.177.137:8084/");

echo "=== ONU ALL LIST WITH REFERER m/onu_all_onu.htm ===\n";
echo postWithReferer("get=onualllist&sysUnit=1", "http://103.59.177.137:8084/m/onu_all_onu.htm") . "\n\n";

echo "=== PON 1 WITH REFERER m/onu_info.htm?ponport=1 ===\n";
echo postWithReferer("get=info&sysUnit=1&ponport=1&displayfunc=displayAll", "http://103.59.177.137:8084/m/onu_info.htm?ponport=1") . "\n\n";

echo "=== PON STATUS WITH REFERER m/olt_pon_status.htm?ponport=1 ===\n";
echo postWithReferer("get=oltponstatus&sysUnit=1&ponport=1", "http://103.59.177.137:8084/m/olt_pon_status.htm?ponport=1") . "\n\n";
