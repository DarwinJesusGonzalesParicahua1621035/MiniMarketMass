<?php
class CategoriaController
{
    const ACCIONES = ['index', 'guardar', 'eliminar'];
    private Categoria $modelo;

    public function __construct(private PDO $db) { $this->modelo = new Categoria($db); }

    public function index(): void
    {
        $editando = isset($_GET['editar']) ? $this->modelo->buscar((int)$_GET['editar']) : null;
        $categorias = $this->modelo->todas();
        $this->vista('categorias/index', compact('categorias', 'editando'));
    }

    public function guardar(): void
    {
        $this->soloPost();
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '' || mb_strlen($nombre) > 60) {
            flash('err', 'El nombre es obligatorio y tiene máximo 60 caracteres.');
            redirigir('productos.php?m=categorias' . ($id ? "&editar=$id" : ''));
        }
        try {
            $id > 0 ? $this->modelo->actualizar($id, $nombre) : $this->modelo->crear($nombre);
            flash('ok', 'Categoría guardada.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'Ya existe una categoría con ese nombre.'));
        }
        redirigir('productos.php?m=categorias');
    }

    public function eliminar(): void
    {
        $this->soloPost();
        try {
            $this->modelo->eliminar((int)($_POST['id'] ?? 0));
            flash('ok', 'Categoría eliminada.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, '', 'No se puede eliminar: tiene productos asociados.'));
        }
        redirigir('productos.php?m=categorias');
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
    }

    private function vista(string $archivo, array $datos = []): void
    {
        extract($datos);
        pagina_inicio('Categorías');
        require __DIR__ . '/../views/' . $archivo . '.php';
        pagina_fin();
    }
}