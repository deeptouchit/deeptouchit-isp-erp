<?php
header('Content-Type: text/plain');

$pages = [
    'm/olt_pon_status.htm',
    'm/onu_authlist.htm',
    'm/onu_info.htm',
    'm/onu_search.htm',
    'm/mac_dynamic_info.htm',
];

foreach ($pages as $page) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) continue;
    $req = "GET /{$page} HTTP/1.1\r\nHost: 103.59.177.137:8084\r\nUser-Agent: Mozilla/5.0\r\nReferer: http://103.59.177.137:8084/index.html\r\nConnection: close\r\n\r\n";
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    
    echo "=== PAGE: {$page} ===\n";
    preg_match_all('/param\s*=\s*["\']([^"\']+)["\']/', $res, $params);
    print_r($params[1]);
    echo "\n";
}
