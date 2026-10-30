<?php
// Funciones de apoyo para mis módulos (escape, CSRF, avisos, validación de fechas).
// Si Darwin ya tiene helpers equivalentes en core/, usa los suyos y borra este archivo.

class Ayuda
{
    public static function e(mixed $valor): string
    {
        return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
    }

    private static function iniciarSesion(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function token(): string
    {
        self::iniciarSesion();
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public static function verificar(): void
    {
        self::iniciarSesion();
        $enviado = $_POST['csrf'] ?? '';
        $guardado = $_SESSION['csrf'] ?? '';
        if (!is_string($enviado) || $guardado === '' || !hash_equals($guardado, $enviado)) {
            http_response_code(403);
            exit('Solicitud no válida.');
        }
    }

    public static function aviso(string $tipo, string $mensaje): void
    {
        self::iniciarSesion();
        $_SESSION['aviso'] = [$tipo, $mensaje];
    }

    public static function tomarAviso(): ?array
    {
        self::iniciarSesion();
        $aviso = $_SESSION['aviso'] ?? null;
        unset($_SESSION['aviso']);
        return $aviso;
    }

    public static function redirigir(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function post(string $clave): string
    {
        $valor = $_POST[$clave] ?? '';
        return is_string($valor) ? trim($valor) : '';
    }

    // Devuelve un DateTime solo si el texto tiene exactamente ese formato.
    public static function fecha(string $texto, string $formato): ?DateTime
    {
        $d = DateTime::createFromFormat('!' . $formato, $texto);
        return ($d && $d->format($formato) === $texto) ? $d : null;
    }

    public static function errorBD(PDOException $e): string
    {
        error_log($e->getMessage());
        $codigo = (int) ($e->errorInfo[1] ?? 0);
        return match ($codigo) {
            1062 => 'Ya existe un registro con ese dato único.',
            1451 => 'No se puede eliminar: el registro está siendo usado por otros datos.',
            1452 => 'Uno de los datos relacionados no existe.',
            3819 => 'Los datos no cumplen las reglas de la base de datos.',
            default => 'Ocurrió un error al procesar la solicitud. Inténtalo de nuevo.',
        };
    }
}