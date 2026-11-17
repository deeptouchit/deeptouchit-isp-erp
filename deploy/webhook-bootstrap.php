<?php

// Temporary transition handler for an existing installation whose PHP worker
// cannot write the repository. The normal public handler replaces this on pull.
header('Content-Type: application/json');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    $base = dirname(__DIR__);
    require $base.'/vendor/autoload.php';
    $settings = Dotenv\Dotenv::parse(file_get_contents($base.'/.env'));
    $secret = $settings['GITHUB_WEBHOOK_SECRET'] ?? '';
    if ($secret === '') {
        http_response_code(503);
        echo json_encode(['error' => 'Deployment authentication unavailable']);
        exit;
    }
    $body = file_get_contents('php://input');
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    if (!hash_equals('sha256='.hash_hmac('sha256', $body, $secret), $signature)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
    $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if (($data['repository']['full_name'] ?? '') !== ($settings['GITHUB_DEPLOY_REPOSITORY'] ?? 'deeptouchit/deeptouchit-isp-erp')) {
        http_response_code(403);
        echo json_encode(['error' => 'Repository not allowed']);
        exit;
    }
    $event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';
    if ($event === 'ping') {
        echo json_encode(['message' => 'Webhook verified']);
        exit;
    }
    $branch = $settings['GITHUB_DEPLOY_BRANCH'] ?? 'refs/heads/main';
    $branch = str_starts_with($branch, 'refs/heads/') ? $branch : 'refs/heads/'.$branch;
    if ($event !== 'push' || ($data['ref'] ?? '') !== $branch || !empty($data['deleted'])) {
        echo json_encode(['message' => 'Event ignored']);
        exit;
    }
    $runner = $settings['GITHUB_DEPLOY_RUNNER'] ?? '';
    if ($runner === '' || !is_executable($runner)) {
        http_response_code(503);
        echo json_encode(['error' => 'Deployment runner unavailable']);
        exit;
    }
    exec('sudo -n -- '.escapeshellarg($runner).' >> '.escapeshellarg($base.'/storage/logs/deploy.log').' 2>&1 < /dev/null &', $output, $status);
    http_response_code($status === 0 ? 202 : 503);
    echo json_encode(['success' => $status === 0, 'message' => 'Deployment scheduled']);
} catch (Throwable $exception) {
    http_response_code(503);
    echo json_encode(['error' => 'Deployment request could not be processed']);
}
