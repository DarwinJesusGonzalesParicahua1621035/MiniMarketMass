<?php
class Auth
{
    private const COOKIE = 'mm_recordar';
    private const DIAS = 30;
    private const ROLES = ['ADMINISTRADOR', 'CAJERO'];

    public static function login(string $usuario, string $clave, bool $recordar = false): bool
    {
        $s = Database::pdo()->prepare('SELECT id, nombre, clave, rol, totp_activo FROM empleado WHERE usuario = ? AND activo = 1');
        $s->execute([$usuario]);
        $u = $s->fetch();
        if (!$u || !in_array($u['rol'], self::ROLES, true) || !password_verify($clave, $u['clave'])) {
            return false;
        }
        $datos = ['id' => (int)$u['id'], 'nombre' => $u['nombre'], 'rol' => $u['rol']];
        if (!empty($u['totp_activo'])) {
            session_regenerate_id(true);
            $_SESSION['2fa'] = $datos + ['recordar' => $recordar, 't' => time(), 'n' => 0];
            return true;
        }
        self::abrir($datos, $recordar);
        return true;
    }

    /* ---------- Segundo paso (código de 6 dígitos) ---------- */

    public static function requiere2fa(): bool
    {
        $p = $_SESSION['2fa'] ?? null;
        if (!$p) {
            return false;
        }
        if (time() - $p['t'] > 300) {
            unset($_SESSION['2fa']);
            return false;
        }
        return true;
    }

    public static function verificar2fa(string $codigo): bool
    {
        if (!self::requiere2fa()) {
            return false;
        }
        $p = $_SESSION['2fa'];
        if ($p['n'] >= 5) {
            unset($_SESSION['2fa']);
            return false;
        }
        $_SESSION['2fa']['n']++;
        $pdo = Database::pdo();
        $s = $pdo->prepare('SELECT totp_secreto, totp_ultimo FROM empleado WHERE id = ? AND activo = 1');
        $s->execute([$p['id']]);
        $r = $s->fetch();
        if (!$r || !$r['totp_secreto']) {
            return false;
        }
        $paso = Totp::verificar($r['totp_secreto'], $codigo);
        if ($paso === null || $paso <= (int)$r['totp_ultimo']) {
            return false;
        }
        $pdo->prepare('UPDATE empleado SET totp_ultimo = ? WHERE id = ?')->execute([$paso, $p['id']]);
        unset($_SESSION['2fa']);
        self::abrir(['id' => $p['id'], 'nombre' => $p['nombre'], 'rol' => $p['rol']], (bool)$p['recordar']);
        return true;
    }

    private static function abrir(array $u, bool $recordar): void
    {
        session_regenerate_id(true);
        $_SESSION['user'] = $u;
        if ($recordar) {
            self::recordar((int)$u['id']);
        }
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

    /* ---------- Mantener sesión ---------- */

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