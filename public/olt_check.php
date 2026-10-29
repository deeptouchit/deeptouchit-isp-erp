<?php
header('Content-Type: text/plain');

$u = "admin";
$p = "admin606245";
$sid = base64_encode("{$u}&{$p}");

$fp = @fsockopen("103.59.177.137", 8084, $errno, $errstr, 5);
if (!$fp) {
    echo "Connection failed";
    exit;
}

// Helper to send HTTP/1.1 request on open socket
function sendHttp($fp, $body, $keepAlive = true) {
    $len = strlen($body);
    $conn = $keepAlive ? 'keep-alive' : 'close';
    $req = "POST /sw.cgi HTTP/1.1\r\n" .
           "Host: 103.59.177.137:8084\r\n" .
           "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
           "Accept: text/javascript, text/html, application/xml, text/xml, */*\r\n" .
           "X-Requested-With: XMLHttpRequest\r\n" .
           "Content-Type: uni_mars_ap\r\n" .
           "Referer: http://103.59.177.137:8084/m/onu_all_onu.htm\r\n" .
           "Content-Length: {$len}\r\n" .
           "Connection: {$conn}\r\n\r\n" .
           $body;
           
    fwrite($fp, $req);
    
    // Read HTTP response
    $header = '';
    while ($line = fgets($fp, 1024)) {
        $header .= $line;
        if (trim($line) === '') break;
    }
    
    $contentLen = 0;
    if (preg_match('/Content-Length:\s*(\d+)/i', $header, $m)) {
        $contentLen = (int)$m[1];
    }
    
    $body = '';
    if ($contentLen > 0) {
        $read = 0;
        while ($read < $contentLen && !feof($fp)) {
            $chunk = fread($fp, min(1024, $contentLen - $read));
            $body .= $chunk;
            $read += strlen($chunk);
        }
    } else {
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            $body .= $line;
            if (trim($line) === '</xml>') break;
        }
    }
    
    return ['header' => $header, 'body' => $body];
}

// 1. Send Login
$res1 = sendHttp($fp, "set=login&user={$sid}", true);
echo "=== 1. LOGIN RESPONSE ===\n" . $res1['body'] . "\n\n";

// 2. Send sysinfo2 on SAME socket
$res2 = sendHttp($fp, "get=sysinfo2&sysunit=1", true);
echo "=== 2. SYSINFO2 RESPONSE ===\n" . $res2['body'] . "\n\n";

// 3. Send onualllist on SAME socket
$res3 = sendHttp($fp, "get=onualllist&sysUnit=1", true);
echo "=== 3. ONU ALL LIST RESPONSE ===\n" . $res3['body'] . "\n\n";

// 4. Send info for ponport 1 on SAME socket
$res4 = sendHttp($fp, "get=info&sysUnit=1&ponport=1&displayfunc=displayAll", false);
echo "=== 4. PON 1 INFO RESPONSE ===\n" . $res4['body'] . "\n\n";

fclose($fp);
