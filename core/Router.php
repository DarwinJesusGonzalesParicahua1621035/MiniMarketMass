<?php
// core/Router.php
class Router {
    private $routes = [];
    public function get($path, $action) { $this->routes['GET'][$path] = $action; }
    public function post($path, $action) { $this->routes['POST'][$path] = $action; }
    
    public function run($uri, $method) {
        $uri = parse_url($uri, PHP_URL_PATH);
        if (isset($this->routes[$method][$uri])) {
            list($controller, $methodAction) = explode('@', $this->routes[$method][$uri]);
            require_once __DIR__ . "/../controllers/{$controller}.php";
            $obj = new $controller();
            $obj->$methodAction();
        } else {
            http_response_code(404);
            echo "404 - Ruta no encontrada";
        }
    }
}