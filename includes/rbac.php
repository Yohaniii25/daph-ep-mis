<?php
/**
 * Centralized Role-Based Access Control (RBAC) Helpers
 *
 * Keeps role permissions, read-only enforcement, and server-side write guards
 * in one place without changing existing page or processor file paths.
 */

if (!function_exists('get_current_user_role')) {
    /**
     * Return the active session role string.
     */
    function get_current_user_role(): string
    {
        return (string)($_SESSION['role'] ?? '');
    }
}

if (!function_exists('is_read_only_role')) {
    /**
     * Check whether the given (or current) role is strictly read-only on shared clinical/reporting modules.
     */
    function is_read_only_role(?string $role = null): bool
    {
        $role = $role ?? get_current_user_role();
        return in_array($role, ['sms', 'deputy_director_hq_1', 'provincial_director'], true);
    }
}

if (!function_exists('is_supervisory_role')) {
    /**
     * Check whether the given (or current) role can switch across veterinary ranges.
     */
    function is_supervisory_role(?string $role = null): bool
    {
        $role = $role ?? get_current_user_role();
        return in_array($role, [
            'sms',
            'deputy_director_hq_1',
            'deputy_director_hq_2',
            'district_dd',
            'deputy_director_district',
            'provincial_director',
            'administrator',
            'admin',
            'super_admin',
            'planning_officer',
        ], true);
    }
}

if (!function_exists('require_module_roles')) {
    /**
     * Enforce session login and role membership for a module page or API endpoint.
     */
    function require_module_roles(array $allowed_roles, bool $json = false, string $redirect_url = '../../../index.php'): void
    {
        if (!isset($_SESSION['logged_in']) || !in_array(get_current_user_role(), $allowed_roles, true)) {
            http_response_code(403);
            if ($json) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
                exit();
            }
            header('Location: ' . $redirect_url);
            exit();
        }
    }
}

if (!function_exists('block_read_only_writes')) {
    /**
     * Block POST/PUT/DELETE and write-action requests for read-only roles (e.g. sms, deputy_director_hq_1).
     */
    function block_read_only_writes(
        array $write_actions = [],
        bool $json = true,
        ?string $redirect_url = null,
        string $message = 'Forbidden: Read-only role cannot modify records.'
    ): void {
        if (!is_read_only_role()) {
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $action = trim((string)($_REQUEST['action'] ?? ''));

        $is_write_method = in_array($method, ['POST', 'PUT', 'DELETE'], true);
        $is_write_action = ($action !== '' && in_array($action, $write_actions, true));

        if ($is_write_method || $is_write_action) {
            http_response_code(403);
            if ($json) {
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['success' => false, 'message' => $message]);
                exit();
            }
            $_SESSION['msg'] = $message;
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($redirect_url ?? '../../../index.php'));
            exit();
        }
    }
}
