<?php
/**
 * Logout script
 * - Destroys session
 * - Removes "remember me" cookie and DB token (if used)
 * - Redirects to homepage
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Try to include auth helper if available
if (file_exists(__DIR__ . '/../includes/auth.php')) {
    require_once __DIR__ . '/../includes/auth.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If Auth class exists and has logout method, use it (handles persistent login tokens)
if (class_exists('Auth')) {
    try {
        $auth = new Auth();
        if (method_exists($auth, 'logout')) {
            $auth->logout();
        }
    } catch (Throwable $e) {
        // ignore and fallback to manual logout
    }
}

// Manual cleanup (in case Auth not available or didn't clear everything)
try {
    // Remove remember_me cookie and DB token if exists
    if (!empty($_COOKIE['remember_me'])) {
        // Cookie format expected: selector:validator
        $parts = explode(':', $_COOKIE['remember_me']);
        $selector = $parts[0] ?? null;

        // Attempt to delete token from persistent_logins table if DB accessible
        if (!empty($selector)) {
            // If get_pdo helper exists, use it; otherwise try to construct PDO from config
            try {
                if (function_exists('get_pdo')) {
                    $pdo = get_pdo();
                } elseif (isset($pdo) && $pdo instanceof PDO) {
                    // use existing
                } else {
                    // create temporary PDO (Postgres)
                    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER')) {
                        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', DB_HOST, defined('DB_PORT') ? DB_PORT : '5432', DB_NAME);
                        $pdo = new PDO($dsn, DB_USER, DB_PASS ?? '', [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                    }
                }

                if (!empty($pdo) && ($pdo instanceof PDO)) {
                    $del = $pdo->prepare('DELETE FROM persistent_logins WHERE selector = :sel');
                    $del->execute(['sel' => $selector]);
                }
            } catch (Throwable $e) {
                // ignore DB errors during logout
                error_log('Logout: could not clear persistent token: ' . $e->getMessage());
            }
        }

        // Expire cookie
        setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
        unset($_COOKIE['remember_me']);
    }
} catch (Throwable $e) {
    // ignore
}

// Log activity if user was logged in
$userId = $_SESSION['user_id'] ?? null;
try {
    if ($userId) {
        if (function_exists('get_pdo')) {
            $logPdo = get_pdo();
        } elseif (!empty($pdo) && ($pdo instanceof PDO)) {
            $logPdo = $pdo;
        } else {
            $logPdo = null;
        }

        if (!empty($logPdo) && ($logPdo instanceof PDO)) {
            $stmt = $logPdo->prepare('INSERT INTO activity_logs (user_id, action, created_at) VALUES (:uid, :action, NOW())');
            $stmt->execute(['uid' => $userId, 'action' => 'LOGOUT']);
        }
    }
} catch (Throwable $e) {
    // ignore logging errors
    error_log('Logout: activity log failed: ' . $e->getMessage());
}

// Clear session data and destroy session
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'] ?? '/',
        $params['domain'] ?? '',
        $params['secure'] ?? false,
        $params['httponly'] ?? true
    );
}
session_destroy();

// Redirect back to homepage (or referer if safe)
$redirect = '/laptop-store/index.php';
if (!empty($_SERVER['HTTP_REFERER'])) {
    $ref = $_SERVER['HTTP_REFERER'];
    // Basic safety: only allow same host redirects
    $host = parse_url($ref, PHP_URL_HOST);
    if ($host === ($_SERVER['HTTP_HOST'] ?? '')) {
        $redirect = $ref;
    }
}

header('Location: ' . $redirect);
exit();