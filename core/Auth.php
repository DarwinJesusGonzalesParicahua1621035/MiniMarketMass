<?php
// core/Auth.php
class Auth {
    public static function check() {
        return isset($_SESSION['user_id']);
    }
    public static function user() {
        return $_SESSION['user_name'] ?? 'Invitado';
    }
    public static function role() {
        return $_SESSION['user_role'] ?? '';
    }
}