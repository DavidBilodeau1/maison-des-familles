<?php
/**
 * Local photo server for Cloudflare Tunnel.
 *
 * Run from repo root:
 *   WEBHOOK_SECRET=your-secret \
 *   UPLOADS_DIR=/Volumes/PHOTOS/photos/uploads \
 *   FINAL_DIR=/Volumes/PHOTOS/photos/final_choices \
 *   php -S localhost:8091 local-server/router.php
 *
 * Unauthenticated:
 *   GET  /{family}/{filename}        → serve photo from uploads
 *
 * Authenticated (Bearer token):
 *   GET  /list-photos/{family}       → list filenames in uploads/{family}/
 *   GET  /list-final/{family}        → list filenames in final_choices/{family}/
 *   POST /create-directory           → create uploads/{family}/ and final_choices/{family}/
 *   POST /move-photos                → move selected files to final_choices/{family}/
 */

$uploadsDir = rtrim(getenv('UPLOADS_DIR') ?: '/Volumes/PHOTOS/photos/uploads', '/');
$finalDir   = rtrim(getenv('FINAL_DIR')   ?: '/Volumes/PHOTOS/photos/final_choices', '/');
$secret     = getenv('WEBHOOK_SECRET');

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ── Helpers ───────────────────────────────────────────────────────────────────

function json_out(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function require_auth(string $secret): void
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($auth !== 'Bearer ' . $secret) {
        json_out(['error' => 'Unauthorized'], 401);
    }
}

function valid_dir_name(string $name): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_\-]+$/', $name);
}

function list_images(string $dir): array
{
    if (!is_dir($dir)) {
        return [];
    }
    $files = [];
    foreach (scandir($dir) as $file) {
        if (in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $files[] = $file;
        }
    }
    return $files;
}

// ── Authenticated GET: list uploads ──────────────────────────────────────────

if ($method === 'GET' && preg_match('#^/list-photos/([^/]+)$#', $path, $m)) {
    require_auth($secret);
    $family = $m[1];
    if (!valid_dir_name($family)) json_out(['error' => 'Invalid directory name'], 400);
    json_out(['files' => list_images($uploadsDir . '/' . $family)]);
}

// ── Authenticated GET: list final_choices ─────────────────────────────────────

if ($method === 'GET' && preg_match('#^/list-final/([^/]+)$#', $path, $m)) {
    require_auth($secret);
    $family = $m[1];
    if (!valid_dir_name($family)) json_out(['error' => 'Invalid directory name'], 400);
    json_out(['files' => list_images($finalDir . '/' . $family)]);
}

// ── POST endpoints ────────────────────────────────────────────────────────────

if ($method === 'POST') {
    require_auth($secret);
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // Create uploads/{family}/ and final_choices/{family}/
    if ($path === '/create-directory') {
        $dirName = $body['directory_name'] ?? '';
        if (!valid_dir_name($dirName)) json_out(['error' => 'Invalid directory name'], 400);

        foreach ([$uploadsDir . '/' . $dirName, $finalDir . '/' . $dirName] as $dir) {
            if (!is_dir($dir)) mkdir($dir, 0755, true);
        }

        json_out(['success' => true]);
    }

    // Move selected photos from uploads/{family}/ to final_choices/{family}/
    if ($path === '/move-photos') {
        $dirName   = $body['directory_name'] ?? '';
        $filenames = $body['filenames'] ?? [];

        if (!valid_dir_name($dirName)) json_out(['error' => 'Invalid directory name'], 400);
        if (!is_array($filenames))     json_out(['error' => 'filenames must be an array'], 400);

        $srcDir  = $uploadsDir . '/' . $dirName;
        $destDir = $finalDir   . '/' . $dirName;

        if (!is_dir($destDir)) mkdir($destDir, 0755, true);

        $moved  = [];
        $errors = [];
        foreach ($filenames as $filename) {
            // Prevent path traversal
            if (!valid_dir_name(pathinfo($filename, PATHINFO_FILENAME)) || !in_array(
                strtolower(pathinfo($filename, PATHINFO_EXTENSION)),
                ['jpg', 'jpeg', 'png', 'gif', 'webp']
            )) {
                $errors[] = $filename;
                continue;
            }

            $src  = $srcDir  . '/' . $filename;
            $dest = $destDir . '/' . $filename;

            if (is_file($src)) {
                rename($src, $dest);
                $moved[] = $filename;
            }
        }

        json_out(['success' => true, 'moved' => $moved, 'errors' => $errors]);
    }

    json_out(['error' => 'Unknown endpoint'], 404);
}

// ── Static file serving: GET /{family}/{filename} ────────────────────────────

if ($method === 'GET' && preg_match('#^/([^/]+)/([^/]+)$#', $path, $m)) {
    $family   = $m[1];
    $filename = $m[2];
    $file     = $uploadsDir . '/' . $family . '/' . $filename;

    // Prevent path traversal
    if (strpos(realpath($file) ?: '', realpath($uploadsDir)) !== 0) {
        json_out(['error' => 'Forbidden'], 403);
    }

    if (is_file($file)) {
        $mime = mime_content_type($file) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
}

json_out(['error' => 'Not found'], 404);
