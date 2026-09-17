<?php
// config/session.php - Hardened Session Initializer

if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        // Enforce strict cookie-only sessions
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');

        // Detect HTTPS (including reverse proxies)
        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        $session_lifetime = isset($_ENV['SESSION_LIFETIME']) ? intval($_ENV['SESSION_LIFETIME']) : 14400; // 4 hours

        // Set secure cookie flags
        session_set_cookie_params([
            'lifetime' => $session_lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $is_https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        session_start();
    } else {
        // Fallback start if headers already sent
        session_start();
    }
}
