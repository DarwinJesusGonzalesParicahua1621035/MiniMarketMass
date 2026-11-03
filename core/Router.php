<?php
class Router
{
    public static function run(): void
    {
        $r = $_GET['r'] ?? 'dashboard/index';
        [$c, $a] = array_pad(explode('/', $r, 2), 2, 'index');
        $class = ucfirst($c) . 'Controller';
        if (!preg_match('/^[a-z]+$/', $c) || !preg_match('/^[a-z]+$/', $a)
            || !class_exists($class) || !method_exists($class, $a)
            || (new ReflectionMethod($class, $a))->isStatic()
            || !(new ReflectionMethod($class, $a))->isPublic()) {
            http_response_code(404);
            exit('Página no encontrada');
        }
        (new $class())->$a();
    }
}