<?php
header('Content-Type: text/plain');

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if ($fp) {
    $req = "GET /index.html HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nReferer: http://103.59.177.137:8084/\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    
    foreach (explode("\n", $res) as $i => $line) {
        if (str_contains($line, 'sysUnit') || str_contains($line, 'sysunit') || str_contains($line, 'frame') || str_contains($line, 'onu')) {
            echo "$i: " . trim($line) . "\n";
        }
    }
}
