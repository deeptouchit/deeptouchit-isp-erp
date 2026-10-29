<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}"); // YWRtaW4mYWRtaW42MDYyNDU=

function testPost($body, $headers = []) {
    $fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
    if (!$fp) return "FAILED";
    
    $len = strlen($body);
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36\r\n" .
           "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
           "X-Requested-With: XMLHttpRequest\r\n" .
           "X-Prototype-Version: 1.5.0\r\n" .
           "Referer: http://103.59.177.137:8084/\r\n";
    foreach ($headers as $k => $v) {
        $req .= "{$k}: {$v}\r\n";
    }
    $req .= "Content-Length: {$len}\r\n" .
            "Connection: close\r\n\r\n" .
            $body;
            
    fwrite($fp, $req);
    $res = '';
    while (!feof($fp)) $res .= fgets($fp, 1024);
    fclose($fp);
    return $res;
}

echo "--- TEST 1: uni_mars_ap ---\n";
echo testPost("set=login&user={$sid}", ['Content-Type' => 'uni_mars_ap']) . "\n";

echo "--- TEST 2: application/x-www-form-urlencoded; charset=UTF-8 ---\n";
echo testPost("set=login&user={$sid}", ['Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8']) . "\n";

echo "--- TEST 3: text/plain ---\n";
echo testPost("set=login&user={$sid}", ['Content-Type' => 'text/plain']) . "\n";
