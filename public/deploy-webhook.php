<?php
/**
 * Standalone GitHub Auto-Deployment Webhook Handler
 * DeepTouch ISP-ERP
 */

header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$data = json_decode($payload, true) ?: [];

// Get headers safely across all server environments
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? $_SERVER['X_GITHUB_EVENT'] ?? '';
if (empty($event) && function_exists('getallheaders')) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $event = $headers['x-github-event'] ?? '';
}
if (empty($event)) {
    $event = 'push';
}

$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? $_SERVER['X_HUB_SIGNATURE_256'] ?? '';
if (empty($signature) && function_exists('getallheaders')) {
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    $signature = $headers['x-hub-signature-256'] ?? '';
}

// 1. Handle GitHub Ping
if ($event === 'ping') {
    echo json_encode([
        'status' => 'success',
        'message' => 'Pong! ISP-ERP Webhook connection verified successfully.'
    ]);
    exit;
}

// 2. Only accept push events
if ($event !== 'push') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => "Event '{$event}' ignored."]);
    exit;
}

// 3. Optional Secret Verification
$secret = getenv('GITHUB_WEBHOOK_SECRET');
if (!empty($secret)) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    if (!hash_equals($expectedSignature, $signature)) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Invalid signature verification.']);
        exit;
    }
}

// 4. Verify Branch (main/master)
$ref = $data['ref'] ?? '';
if ($ref !== 'refs/heads/main' && $ref !== 'refs/heads/master') {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'message' => "Push on '{$ref}' ignored."]);
    exit;
}

// 5. Trigger Deploy Script
$baseDir = dirname(__DIR__);
$deployScript = $baseDir . '/deploy.sh';
$logFile = $baseDir . '/storage/logs/deploy.log';

if (!file_exists(dirname($logFile))) {
    @mkdir(dirname($logFile), 0775, true);
}

$commit = $data['head_commit']['id'] ?? 'unknown';
$author = $data['head_commit']['author']['name'] ?? 'unknown';
$message = $data['head_commit']['message'] ?? '';

file_put_contents($logFile, date('[Y-m-d H:i:s]') . " Webhook triggered by {$author} ({$commit}): {$message}\n", FILE_APPEND);

if (file_exists($deployScript)) {
    $cmd = "bash {$deployScript} >> {$logFile} 2>&1 &";
    exec($cmd);

    echo json_encode([
        'status' => 'success',
        'message' => 'Deployment script initiated in background.',
        'commit' => $commit,
        'author' => $author,
        'timestamp' => date('c')
    ]);
} else {
    // In-place fallback pull
    $cmd = "cd {$baseDir} && git fetch origin main && git reset --hard origin/main && php artisan optimize:clear >> {$logFile} 2>&1 &";
    exec($cmd);

    echo json_encode([
        'status' => 'success',
        'message' => 'Direct git reset triggered.',
        'commit' => $commit,
        'timestamp' => date('c')
    ]);
}
