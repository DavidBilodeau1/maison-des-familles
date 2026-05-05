<?php
/**
 * Local photo server for Cloudflare Tunnel.
 *
 * Run from repo root:
 *   WEBHOOK_SECRET=your-secret php -S localhost:8091 -t storage/app/photos/uploads local-server/router.php
 *
 * GET  /{family}/{filename}  → serves the photo file statically
 * POST /create-directory     → creates a family uploads directory (authenticated)
 */

$uploadsDir = realpath(__DIR__ . '/../storage/app/photos/uploads');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/create-directory') {
    header('Content-Type: application/json');

    $secret = getenv('WEBHOOK_SECRET');
    if (!$secret) {
        http_response_code(500);
        echo json_encode(['error' => 'WEBHOOK_SECRET env var not set on local server']);
        exit;
    }

    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== 'Bearer ' . $secret) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true);
    $dirName = $body['directory_name'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $dirName)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid directory name']);
        exit;
    }

    $path = $uploadsDir . DIRECTORY_SEPARATOR . $dirName;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }

    echo json_encode(['success' => true, 'path' => $path]);
    exit;
}

// All other requests: let PHP's built-in server serve the file statically.
return false;
