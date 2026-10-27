<?php
class ProductoController
{
    const ACCIONES = ['index', 'nuevo', 'editar', 'guardar', 'estado', 'historial'];
    private Producto $modelo;
    private Categoria $categorias;

    public function __construct(private PDO $db)
    {
        $this->modelo = new Producto($db);
        $this->categorias = new Categoria($db);
    }

    public function index(): void
    {
        $q = trim($_GET['q'] ?? '');
        $cat = (int)($_GET['categoria'] ?? 0);
        $estado = $_GET['estado'] ?? '';
        $productos = $this->modelo->listar($q, $cat, $estado);
        $categorias = $this->categorias->todas();
        $this->vista('productos/index', compact('productos', 'categorias', 'q', 'cat', 'estado'));
    }

    public function nuevo(): void
    {
        $this->formulario([], []);
    }

    public function editar(): void
    {
        $p = $this->modelo->buscar((int)($_GET['id'] ?? 0));
        if (!$p) { flash('err', 'Producto no encontrado.'); redirigir('productos.php?m=productos'); }
        $this->formulario($p, []);
    }

    public function guardar(): void
    {
        $this->soloPost();
        $id = (int)($_POST['id'] ?? 0);
        [$d, $errores] = $this->leerFormulario();

        if ($errores) {
            $d['id'] = $id;
            $this->formulario($d, $errores);
            return;
        }
        try {
            if ($id > 0) { $this->modelo->actualizar($id, $d); }
            else         { $this->modelo->crear($d); }
            flash('ok', 'Producto guardado.');
            redirigir('productos.php?m=productos');
        } catch (PDOException $ex) {
            $d['id'] = $id;
            $this->formulario($d, [mensaje_error($ex, 'Ya existe un producto con ese código de barras.')]);
        }
    }

    public function estado(): void
    {
        $this->soloPost();
        $this->modelo->cambiarEstado((int)($_POST['id'] ?? 0), (int)($_POST['activo'] ?? 0));
        flash('ok', 'Estado actualizado.');
        redirigir('productos.php?m=productos');
    }

    public function historial(): void
    {
        $producto = $this->modelo->buscar((int)($_GET['id'] ?? 0));
        if (!$producto) { flash('err', 'Producto no encontrado.'); redirigir('productos.php?m=productos'); }
        $historial = $this->modelo->historial((int)$producto['id']);
        $this->vista('productos/historial', compact('producto', 'historial'));
    }

    // Valida con las mismas reglas del schema (README: CHECK y UNIQUE).
    private function leerFormulario(): array
    {
        $d = [
            'categoria_id'  => (int)($_POST['categoria_id'] ?? 0),
            'codigo_barras' => trim($_POST['codigo_barras'] ?? ''),
            'nombre'        => trim($_POST['nombre'] ?? ''),
            'unidad'        => trim($_POST['unidad'] ?? ''),
            'precio'        => trim($_POST['precio'] ?? ''),
        ];
        $err = [];
        if ($d['categoria_id'] <= 0 || !$this->categorias->buscar($d['categoria_id'])) $err[] = 'Elige una categoría válida.';
        if (mb_strlen($d['codigo_barras']) !== 13) $err[] = 'El código de barras debe tener exactamente 13 caracteres.';
        if ($d['nombre'] === '' || mb_strlen($d['nombre']) > 120) $err[] = 'El nombre es obligatorio (máximo 120 caracteres).';
        if ($d['unidad'] === '' || mb_strlen($d['unidad']) > 20) $err[] = 'La unidad es obligatoria (máximo 20 caracteres).';
        if (!is_numeric($d['precio']) || (float)$d['precio'] < 0 || (float)$d['precio'] > 99999999.99) {
            $err[] = 'El precio debe ser un número mayor o igual a 0.';
        } else {
            $d['precio'] = number_format((float)$d['precio'], 2, '.', '');
        }
        return [$d, $err];
    }

    private function formulario(array $producto, array $errores): void
    {
        $categorias = $this->categorias->todas();
        $this->vista('productos/form', compact('producto', 'categorias', 'errores'));
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
    }

    private function vista(string $archivo, array $datos = []): void
    {
        extract($datos);
        pagina_inicio('Productos');
        require __DIR__ . '/../views/' . $archivo . '.php';
        pagina_fin();
    }
}