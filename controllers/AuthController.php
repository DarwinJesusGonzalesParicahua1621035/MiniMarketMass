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
            if (Auth::login(trim($_POST['usuario'] ?? ''), $_POST['clave'] ?? '')) {
                $this->redirect(Auth::inicio(Auth::user()['rol']));
            }
            $error = 'Usuario o clave incorrectos, o sin acceso al sistema.';
        }
        $this->render('auth/login', ['error' => $error], false);
    }

    public function salir(): void
    {
        Auth::logout();
        header('Location: ' . self::url('auth/login'));
        exit;
    }
}