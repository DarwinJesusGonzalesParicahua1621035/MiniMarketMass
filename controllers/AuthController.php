<?php
class AuthController extends Controller
{
    protected bool $publico = true;

    public function login(): void
    {
        if ($u = Auth::user()) {
            $this->redirect(Auth::inicio($u['rol']));
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::login(trim($_POST['usuario'] ?? ''), $_POST['clave'] ?? '', !empty($_POST['recordar']))) {
                $this->redirect(Auth::requiere2fa() ? 'auth/verificar' : Auth::inicio(Auth::user()['rol']));
            }
            $error = 'Usuario o clave incorrectos, o sin acceso al sistema.';
        }
        $this->render('auth/login', ['error' => $error], false);
    }

    public function verificar(): void
    {
        if (!Auth::requiere2fa()) {
            $this->redirect('auth/login');
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::verificar2fa(preg_replace('/\D/', '', $_POST['codigo'] ?? ''))) {
                $this->redirect(Auth::inicio(Auth::user()['rol']));
            }
            if (!Auth::requiere2fa()) {
                $this->redirect('auth/login');
            }
            $error = 'El código no es válido. Revisa tu app e inténtalo de nuevo.';
        }
        $this->render('auth/verificar', ['error' => $error], false);
    }

    public function seguridad(): void
    {
        $u = Auth::user();
        if (!$u) {
            $this->redirect('auth/login');
        }
        $pdo = Database::pdo();
        $ok = $error = null;
        $q = $pdo->prepare('SELECT totp_activo, clave FROM empleado WHERE id = ?');
        $q->execute([$u['id']]);
        $f = $q->fetch();
        $activo = !empty($f['totp_activo']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $accion = $_POST['accion'] ?? '';
            if ($accion === 'activar' && !$activo) {
                $sec = $_SESSION['totp_nuevo'] ?? '';
                $paso = $sec ? Totp::verificar($sec, preg_replace('/\D/', '', $_POST['codigo'] ?? '')) : null;
                if ($paso !== null) {
                    $pdo->prepare('UPDATE empleado SET totp_secreto = ?, totp_activo = 1, totp_ultimo = ? WHERE id = ?')
                        ->execute([$sec, $paso, $u['id']]);
                    unset($_SESSION['totp_nuevo']);
                    $activo = true;
                    $ok = 'Listo. Desde ahora te pediremos un código al iniciar sesión.';
                } else {
                    $error = 'El código no coincide. Revisa que la hora de tu celular sea automática.';
                }
            } elseif ($accion === 'desactivar' && $activo) {
                if (password_verify($_POST['clave'] ?? '', $f['clave'])) {
                    $pdo->prepare('UPDATE empleado SET totp_secreto = NULL, totp_activo = 0, totp_ultimo = NULL WHERE id = ?')
                        ->execute([$u['id']]);
                    $activo = false;
                    $ok = 'Se desactivó el segundo paso.';
                } else {
                    $error = 'La contraseña no es correcta.';
                }
            }
        }

        $secreto = $uri = null;
        if (!$activo) {
            $secreto = $_SESSION['totp_nuevo'] ??= Totp::secreto();
            $uri = Totp::uri($secreto, $u['nombre']);
        }
        $solo = $u['rol'] === 'CAJERO';
        $this->render('auth/seguridad', compact('activo', 'secreto', 'uri', 'ok', 'error', 'solo') + ['titulo' => 'Seguridad'], !$solo);
    }

     public function salir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . self::url('auth/login'));
            exit;
        }
        Auth::logout();
        header('Location: ' . self::url('auth/login'));
        exit;
    }
}