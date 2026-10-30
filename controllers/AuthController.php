<?php
// controllers/AuthController.php
require_once __DIR__ . '/../models/empleado.php';

class AuthController {
    public function showLogin() {
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function login() {
        $usuario = $_POST['usuario'] ?? '';
        $clave = $_POST['clave'] ?? '';
        
        $empleadoModel = new Empleado();
        $user = $empleadoModel->findByUsername($usuario);
        
        // En producción usa password_verify(), aquí es texto plano para el ejemplo
        if ($user && $user['clave'] === $clave && $user['activo'] == 1) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_role'] = $user['rol'];
            header('Location: /');
        } else {
            header('Location: /login?error=1');
        }
    }

    public function logout() {
        session_destroy();
        header('Location: /login');
    }
}