<?php
abstract class Controller
{
    protected bool $publico = false;
    protected array $roles = ['ADMINISTRADOR'];

    public function __construct()
    {
        if (!$this->publico) {
            $u = Auth::user();
            if (!$u) {
                $this->redirect('auth/login');
            }
            if (!in_array($u['rol'], $this->roles, true)) {
                $this->redirect(Auth::inicio($u['rol']));
            }
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST'
            && !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(419);
            exit('Token de seguridad inválido. Recarga la página.');
        }
    }

    public static function base(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
    }

    public static function url(string $r = '', array $q = []): string
    {
        return self::base() . '/index.php?r=' . $r . ($q ? '&' . http_build_query($q) : '');
    }

    public static function csrf(): string
    {
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }

    protected function redirect(string $r, array $q = []): void
    {
        header('Location: ' . self::url($r, $q));
        exit;
    }

    protected function flash(string $msg, string $tipo = 'ok'): void
    {
        $_SESSION['flash'] = [$tipo, $msg];
    }

    protected function render(string $vista, array $datos = [], bool $layout = true): void
    {
        extract($datos);
        $user = Auth::user();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        ob_start();
        require ROOT . "/views/$vista.php";
        $contenido = ob_get_clean();
                if ($layout) {
            require ROOT . '/views/layout/main.php';
        } else {
            echo $contenido;
        }
    }

    protected function mensaje(Throwable $e): string
    {
        if ($e instanceof PDOException) {
            return match ($e->errorInfo[1] ?? 0) {
                1062 => 'ya existe un registro con ese valor único.',
                1451 => 'tiene registros relacionados y no se puede eliminar.',
                1452 => 'la referencia elegida no existe.',
                3819 => 'no cumple una regla de validación (revisa los datos o el stock disponible).',
                1406 => 'un valor es demasiado largo.',
                default => $e->getMessage(),
            };
        }
        return $e->getMessage();
    }
}