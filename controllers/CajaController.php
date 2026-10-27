<?php
class CajaController
{
    const ACCIONES = ['index', 'guardar', 'eliminar'];
    private Caja $modelo;

    public function __construct(private PDO $db) { $this->modelo = new Caja($db); }

    public function index(): void
    {
        $id = (int)($_GET['editar'] ?? 0);
        $editando = $id > 0 ? $this->modelo->buscar($id) : null;
        if ($id > 0 && !$editando) {
            flash('err', 'Caja no encontrada.');
            redirigir('productos.php?m=caja');
        }
        $cajas = $this->modelo->listar();
        $sucursales = $this->modelo->sucursales();
        $this->vista('caja/index', compact('cajas', 'sucursales', 'editando'));
    }

    public function guardar(): void
    {
        $this->soloPost();
        $id = (int)($_POST['id'] ?? 0);
        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $sucursales = $this->modelo->sucursales();
        $idsSucursales = array_map(static fn($s) => (int)$s['id'], $sucursales);

        if (!in_array($sucursalId, $idsSucursales, true) || $nombre === '' || mb_strlen($nombre) > 40) {
            flash('err', 'Selecciona una sucursal y escribe un nombre de hasta 40 caracteres.');
            redirigir('productos.php?m=caja' . ($id > 0 ? "&editar=$id" : ''));
        }

        try {
            $this->modelo->guardar($id, $sucursalId, $nombre);
            flash('ok', 'Caja guardada.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'Ya existe una caja con ese nombre en la sucursal.'));
        } catch (RuntimeException $ex) {
            flash('err', $ex->getMessage());
        }
        redirigir('productos.php?m=caja');
    }

    public function eliminar(): void
    {
        $this->soloPost();
        try {
            $this->modelo->eliminar((int)($_POST['id'] ?? 0));
            flash('ok', 'Caja eliminada.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, '', 'No se puede eliminar una caja con historial asociado.'));
        }
        redirigir('productos.php?m=caja');
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
    }

    private function vista(string $archivo, array $datos = []): void
    {
        extract($datos);
        pagina_inicio('Cajas');
        require __DIR__ . '/../views/' . $archivo . '.php';
        pagina_fin();
    }
}
