<?php
require_once __DIR__ . '/../core/ModuleSupport.php';
require_once __DIR__ . '/../models/Caja.php';

class CajaController
{
    private Caja $model;

    public function __construct()
    {
        $this->model = new Caja();
    }

    public function index(): void
    {
        $editId = (int)($_GET['editar'] ?? 0);
        $editing = $editId > 0 ? $this->model->find($editId) : null;
        if ($editId > 0 && !$editing) {
            ModuleSupport::flash('err', 'No se encontró la caja.');
            ModuleSupport::redirect('/caja');
        }

        ModuleSupport::render('caja/index', 'Cajas', [
            'cajas' => $this->model->getAll(),
            'sucursales' => $this->model->getSucursales(),
            'editing' => $editing,
            'flash' => ModuleSupport::consumeFlash(),
        ]);
    }

    public function save(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $sucursalIds = array_map(
            static fn(array $sucursal): int => (int)$sucursal['id'],
            $this->model->getSucursales()
        );
        if ($id < 0 || !in_array($sucursalId, $sucursalIds, true)
            || $nombre === '' || mb_strlen($nombre) > 40) {
            ModuleSupport::flash('err', 'Selecciona una sucursal y escribe un nombre de hasta 40 caracteres.');
            ModuleSupport::redirect('/caja');
        }

        try {
            $this->model->save($id, $sucursalId, $nombre);
            ModuleSupport::flash('ok', 'Caja guardada correctamente.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $code = (int)($exception->errorInfo[1] ?? 0);
            ModuleSupport::flash(
                'err',
                $code === 1062
                    ? 'Ya existe una caja con ese nombre en la sucursal.'
                    : 'No se pudo guardar la caja.'
            );
        } catch (RuntimeException $exception) {
            ModuleSupport::flash('err', $exception->getMessage());
        }
        ModuleSupport::redirect('/caja');
    }

    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            ModuleSupport::flash('err', 'La caja seleccionada no es válida.');
            ModuleSupport::redirect('/caja');
        }

        try {
            $deleted = $this->model->delete($id);
            ModuleSupport::flash($deleted ? 'ok' : 'err', $deleted ? 'Caja eliminada.' : 'No se encontró la caja.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            $code = (int)($exception->errorInfo[1] ?? 0);
            ModuleSupport::flash(
                'err',
                $code === 1451
                    ? 'No se puede eliminar una caja con historial asociado.'
                    : 'No se pudo eliminar la caja.'
            );
        }
        ModuleSupport::redirect('/caja');
    }
}
