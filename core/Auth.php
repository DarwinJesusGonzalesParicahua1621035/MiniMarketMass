<?php
class Auth
{
    private const COOKIE = 'mm_recordar';
    private const DIAS = 30;
    private const ROLES = ['ADMINISTRADOR', 'CAJERO'];

    public static function login(string $usuario, string $clave, bool $recordar = false): bool
    {
        $s = Database::pdo()->prepare('SELECT id, nombre, clave, rol FROM empleado WHERE usuario = ? AND activo = 1');
        $s->execute([$usuario]);
        $u = $s->fetch();
        if (!$u || !in_array($u['rol'], self::ROLES, true) || !password_verify($clave, $u['clave'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol']];
        if ($recordar) {
            self::recordar((int)$u['id']);
        }
        return true;
    }

    public static function inicio(string $rol): string
    {
        return $rol === 'CAJERO' ? 'cajero/index' : 'dashboard/index';
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? self::restaurar();
    }

    public static function logout(): void
    {
        self::olvidar();
        $_SESSION = [];
        session_destroy();
    }

    private static function cookie(string $valor, int $expira): void
    {
        if (headers_sent()) {
            return;
        }
        setcookie(self::COOKIE, $valor, [
            'expires'  => $expira,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function recordar(int $empleadoId): void
    {
        try {
            $selector  = bin2hex(random_bytes(9));
            $validador = bin2hex(random_bytes(32));
            $expira    = time() + self::DIAS * 86400;
            Database::pdo()->prepare(
                'INSERT INTO sesion_recordada (empleado_id, selector, validador_hash, expira, agente) VALUES (?,?,?,?,?)'
            )->execute([
                $empleadoId, $selector, hash('sha256', $validador),
                date('Y-m-d H:i:s', $expira), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            ]);
            self::cookie($selector . ':' . $validador, $expira);
        } catch (Throwable $e) {
        }
    }

    private static function restaurar(): ?array
    {
        if (!preg_match('/^([a-f0-9]{18}):([a-f0-9]{64})$/', $_COOKIE[self::COOKIE] ?? '', $m)) {
            return null;
        }
        try {
            $pdo = Database::pdo();
            $s = $pdo->prepare(
                'SELECT r.id, r.validador_hash, e.id AS eid, e.nombre, e.rol
                   FROM sesion_recordada r JOIN empleado e ON e.id = r.empleado_id
                  WHERE r.selector = ? AND r.expira > ? AND e.activo = 1'
            );
            $s->execute([$m[1], date('Y-m-d H:i:s')]);
            $r = $s->fetch();

            if (!$r || !in_array($r['rol'], self::ROLES, true) || !hash_equals($r['validador_hash'], hash('sha256', $m[2]))) {
                if ($r) {
                    $pdo->prepare('DELETE FROM sesion_recordada WHERE id = ?')->execute([$r['id']]);
                }
                self::cookie('', time() - 3600);
                return null;
            }

            $nuevo  = bin2hex(random_bytes(32));
            $expira = time() + self::DIAS * 86400;
            $pdo->prepare('UPDATE sesion_recordada SET validador_hash = ?, expira = ? WHERE id = ?')
                ->execute([hash('sha256', $nuevo), date('Y-m-d H:i:s', $expira), $r['id']]);
            self::cookie($m[1] . ':' . $nuevo, $expira);

            session_regenerate_id(true);
            return $_SESSION['user'] = ['id' => (int)$r['eid'], 'nombre' => $r['nombre'], 'rol' => $r['rol']];
        } catch (Throwable $e) {
            return null;
        }
    }

    private static function olvidar(): void
    {
        if (preg_match('/^([a-f0-9]{18}):/', $_COOKIE[self::COOKIE] ?? '', $m)) {
            try {
                Database::pdo()->prepare('DELETE FROM sesion_recordada WHERE selector = ?')->execute([$m[1]]);
            } catch (Throwable $e) {}
        }
        self::cookie('', time() - 3600);
    }
}
