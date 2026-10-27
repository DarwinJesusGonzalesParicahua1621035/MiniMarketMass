<?php
require_once __DIR__ . '/../core/ModuleSupport.php';
require_once __DIR__ . '/../models/Incidente.php';

class IncidenteController
{
    private Incidente $model;

    public function __construct()
    {
        $this->model = new Incidente();
    }

    public function index(): void
    {
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['', 'ABIERTO', 'RESUELTO'], true)) {
            $estado = '';
        }
        $sucursalId = (int)($_GET['sucursal'] ?? 0);
        ModuleSupport::render('incidentes/index', 'Incidentes', [
            'incidentes' => $this->model->getAll($estado, $sucursalId),
            'sucursales' => $this->model->getSucursales(),
            'estado' => $estado,
            'sucursalId' => $sucursalId,
            'flash' => ModuleSupport::consumeFlash(),
        ]);
    }

    public function create(): void
    {
        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);
        $tipo = trim($_POST['tipo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $sucursalIds = array_map(
            static fn(array $sucursal): int => (int)$sucursal['id'],
            $this->model->getSucursales()
        );
        if (!in_array($sucursalId, $sucursalIds, true)
            || $tipo === '' || mb_strlen($tipo) > 20
            || $descripcion === '' || mb_strlen($descripcion) > 250) {
            ModuleSupport::flash('err', 'Elige una sucursal y completa el tipo (máx. 20) y la descripción (máx. 250).');
            ModuleSupport::redirect('/incidentes');
        }

        try {
            $this->model->create($sucursalId, $tipo, $descripcion);
            ModuleSupport::flash('ok', 'Incidente registrado como abierto.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            ModuleSupport::flash('err', 'No se pudo registrar el incidente.');
        }
        ModuleSupport::redirect('/incidentes');
    }

    public function updateState(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if ($id <= 0 || !in_array($estado, ['ABIERTO', 'RESUELTO'], true)) {
            ModuleSupport::flash('err', 'El incidente o el estado indicado no son válidos.');
            ModuleSupport::redirect('/incidentes');
        }

        try {
            if ($this->model->setState($id, $estado)) {
                ModuleSupport::flash('ok', 'Estado del incidente actualizado.');
            } else {
                ModuleSupport::flash('err', 'No se encontró el incidente o el estado ya era el indicado.');
            }
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            ModuleSupport::flash('err', 'No se pudo actualizar el incidente.');
        }
        ModuleSupport::redirect('/incidentes');
    }
}
