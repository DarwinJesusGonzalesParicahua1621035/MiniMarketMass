<?php
class AuthController extends Controller
{
    protected bool $publico = true;

    public function login(): void
    {
        if (Auth::user()) {
            $this->redirect('dashboard/index');
        }
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (Auth::login(trim($_POST['usuario'] ?? ''), $_POST['clave'] ?? '')) {
                $this->redirect('dashboard/index');
            }
            $error = 'Usuario o clave incorrectos, o sin acceso de administrador.';
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