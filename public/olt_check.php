<?php
header('Content-Type: text/plain');

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if ($fp) {
    $req = "GET /left_frame.html HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nReferer: http://103.59.177.137:8084/index.html\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    
    // Find all links or pages referenced
    preg_match_all('/([a-zA-Z0-9_\/]+\.htm[l]?)/', $res, $matches);
    echo "PAGES IN left_frame.html:\n";
    print_r(array_unique($matches[0]));
}
