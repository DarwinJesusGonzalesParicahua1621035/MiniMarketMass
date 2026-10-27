<?php
class Auth
{
    public static function login(string $usuario, string $clave): bool
    {
        $s = Database::pdo()->prepare('SELECT id, nombre, clave, rol FROM empleado WHERE usuario = ? AND activo = 1');
        $s->execute([$usuario]);
        $u = $s->fetch();
        if (!$u || $u['rol'] !== 'ADMINISTRADOR' || !password_verify($clave, $u['clave'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol']];
        return true;
    }

    public static function user(): ?array { return $_SESSION['user'] ?? null; }

    public static function logout(): void { $_SESSION = []; session_destroy(); }
}