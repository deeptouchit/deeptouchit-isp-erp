<?php
header('Content-Type: text/plain');

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if ($fp) {
    $req = "GET /js/ajax.js HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    
    foreach (explode("\n", $res) as $i => $line) {
        if (str_contains($line, 'var url') || str_contains($line, 'url=') || str_contains($line, 'url =') || str_contains($line, 'sw.cgi')) {
            echo "$i: $line\n";
        }
    }
}
