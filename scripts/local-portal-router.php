<?php
declare(strict_types=1);

// Local proof router: serve frontend assets and execute the attendee PHP API
// on one origin, matching the production contract.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$frontendRoot = realpath(__DIR__ . '/../frontend');
$backendRoot = realpath(__DIR__ . '/../backend');

$cleanPortalPages = [
    '/portal/' => '/portal/index.html',
    '/portal/login' => '/portal/auth/login.html',
    '/portal/register' => '/portal/auth/register.html',
    '/portal/verify' => '/portal/auth/verify.html',
    '/portal/recovery' => '/portal/auth/recovery.html',
    '/portal/reset' => '/portal/auth/reset.html',
    '/portal/admin/' => '/portal/committee/index.html',
];

if ($path === '/portal') {
    header('Location: /portal/', true, 308);
    return true;
}

if ($path === '/portal/committee' || $path === '/portal/committee/') { header('Location: /portal/admin/', true, 308); return true; }
if ($path === '/portal/owner' || $path === '/portal/owner/') { header('Location: /portal/admin/#platform', true, 308); return true; }

if ($path === '/admin/login.php' || $path === '/committee/login') {
    header('Location: /portal/login', true, 302);
    return true;
}

if (isset($cleanPortalPages[$path])) {
    $page = realpath($frontendRoot . $cleanPortalPages[$path]);
    if ($page && str_starts_with($page, $frontendRoot . DIRECTORY_SEPARATOR)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($page);
        return true;
    }
}

if (preg_match('#^/portal/([a-z0-9-]+\.php)$#', $path, $match)) {
    $apiFile = $backendRoot . '/portal/' . $match[1];
    if (is_file($apiFile)) {
        require $apiFile;
        return true;
    }
}

if (preg_match('#^/portal/(staff|committee|owner)/([a-z0-9-]+\.php)$#', $path, $match)) {
    $apiFile = $backendRoot . '/portal/' . $match[1] . '/' . $match[2];
    if (is_file($apiFile)) {
        require $apiFile;
        return true;
    }
}

$candidate = realpath($frontendRoot . ($path === '/' ? '/index.html' : $path));
if ($candidate && str_starts_with($candidate, $frontendRoot . DIRECTORY_SEPARATOR) && is_file($candidate)) {
    return false;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not found\n";
return true;
