<?php
header('Content-Type: text/plain');

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if ($fp) {
    $req = "GET /js/ajax.js HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    
    // Find sendRequest definition
    if (preg_match('/function\s+sendRequest[^{]*\{([^}]+(\{[^}]*\})*[^}]*)\}/s', $res, $m)) {
        echo $m[0];
    } else {
        // Output lines with sendRequest
        foreach (explode("\n", $res) as $i => $line) {
            if (str_contains($line, 'sendRequest')) {
                echo "$i: $line\n";
            }
        }
    }
}
