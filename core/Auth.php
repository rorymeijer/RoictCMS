<?php
class Auth {
    private static $db;

    public static function init(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        self::$db = Database::getInstance();
    }

    public static function login(string $username, string $password): bool {
        $user = self::$db->fetch(
            "SELECT * FROM `" . DB_PREFIX . "users` WHERE (username = ? OR email = ?) AND status = 'active' LIMIT 1",
            [$username, $username]
        );
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['username'];
            self::$db->update(DB_PREFIX . 'users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            return true;
        }
        return false;
    }

    public static function logout(): void {
        session_destroy();
        session_start();
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool {
        return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'super_admin']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . BASE_URL . '/admin/login.php');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: ' . BASE_URL . '/admin/');
            exit;
        }
    }

    public static function currentUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return self::$db->fetch(
            "SELECT id, username, email, role, avatar FROM `" . DB_PREFIX . "users` WHERE id = ?",
            [$_SESSION['user_id']]
        );
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
