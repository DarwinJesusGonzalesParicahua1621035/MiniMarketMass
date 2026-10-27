<?php
class IncidenteController
{
    const ACCIONES = ['index', 'crear', 'estado'];
    private Incidente $modelo;

    public function __construct(private PDO $db) { $this->modelo = new Incidente($db); }

    public function index(): void
    {
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['', 'ABIERTO', 'RESUELTO'], true)) {
            $estado = '';
        }
        $sucursalId = (int)($_GET['sucursal'] ?? 0);
        $incidentes = $this->modelo->listar($estado, $sucursalId);
        $sucursales = $this->modelo->sucursales();
        $this->vista('incidentes/index', compact('incidentes', 'sucursales', 'estado', 'sucursalId'));
    }

    public function crear(): void
    {
        $this->soloPost();
        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $sucursales = $this->modelo->sucursales();
        $idsSucursales = array_map(static fn($s) => (int)$s['id'], $sucursales);

        if (!in_array($sucursalId, $idsSucursales, true)
            || $tipo === '' || mb_strlen($tipo) > 20
            || $descripcion === '' || mb_strlen($descripcion) > 250) {
            flash('err', 'Selecciona una sucursal, indica un tipo (máximo 20 caracteres) y una descripción (máximo 250 caracteres).');
            redirigir('productos.php?m=incidentes');
        }

        try {
            $this->modelo->crear($sucursalId, $tipo, $descripcion);
            flash('ok', 'Incidente registrado como abierto.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'No se pudo registrar el incidente.'));
        }
        redirigir('productos.php?m=incidentes');
    }

    public function estado(): void
    {
        $this->soloPost();
        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($id <= 0 || !in_array($estado, ['ABIERTO', 'RESUELTO'], true)) {
            flash('err', 'El incidente o el estado indicado no son válidos.');
            redirigir('productos.php?m=incidentes');
        }

        try {
            $this->modelo->cambiarEstado($id, $estado);
            flash('ok', 'Estado del incidente actualizado.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'No se pudo actualizar el incidente.'));
        }
        redirigir('productos.php?m=incidentes');
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
    }

    private function vista(string $archivo, array $datos = []): void
    {
        extract($datos);
        pagina_inicio('Incidentes');
        require __DIR__ . '/../views/' . $archivo . '.php';
        pagina_fin();
    }
}
