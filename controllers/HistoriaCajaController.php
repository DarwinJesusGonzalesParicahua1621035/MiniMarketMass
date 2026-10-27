<?php
require_once __DIR__ . '/../core/ModuleSupport.php';
require_once __DIR__ . '/../models/HistoriaCaja.php';

class HistoriaCajaController
{
    private HistoriaCaja $model;

    public function __construct()
    {
        $this->model = new HistoriaCaja();
    }

    public function index(): void
    {
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['', 'ABIERTA', 'CERRADA'], true)) {
            $estado = '';
        }
        ModuleSupport::render('caja/historial', 'Historial de caja', [
            'historial' => $this->model->getAll($estado),
            'cajas' => $this->model->getCajas(),
            'empleados' => $this->model->getEmpleados(),
            'estado' => $estado,
            'flash' => ModuleSupport::consumeFlash(),
        ]);
    }

    public function open(): void
    {
        $cajaId = (int)($_POST['caja_id'] ?? 0);
        $empleadoId = (int)($_POST['empleado_id'] ?? 0);
        $monto = trim($_POST['monto_inicial'] ?? '');
        if ($cajaId <= 0 || $empleadoId <= 0 || !$this->validAmount($monto)) {
            ModuleSupport::flash('err', 'Elige una caja y un empleado; el monto debe ser no negativo y tener hasta dos decimales.');
            ModuleSupport::redirect('/historia-caja');
        }

        try {
            $this->model->open($cajaId, $empleadoId, number_format((float)$monto, 2, '.', ''));
            ModuleSupport::flash('ok', 'Caja abierta correctamente.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            ModuleSupport::flash('err', 'No se pudo registrar la apertura.');
        } catch (RuntimeException $exception) {
            ModuleSupport::flash('err', $exception->getMessage());
        }
        ModuleSupport::redirect('/historia-caja');
    }

    public function close(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $monto = trim($_POST['monto_final'] ?? '');
        if ($id <= 0 || !$this->validAmount($monto)) {
            ModuleSupport::flash('err', 'El monto final debe ser no negativo y tener hasta dos decimales.');
            ModuleSupport::redirect('/historia-caja');
        }

        try {
            $this->model->close($id, number_format((float)$monto, 2, '.', ''));
            ModuleSupport::flash('ok', 'Caja cerrada correctamente.');
        } catch (PDOException $exception) {
            error_log($exception->getMessage());
            ModuleSupport::flash('err', 'No se pudo registrar el cierre.');
        } catch (RuntimeException $exception) {
            ModuleSupport::flash('err', $exception->getMessage());
        }
        ModuleSupport::redirect('/historia-caja');
    }

    private function validAmount(string $amount): bool
    {
        return preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $amount) === 1;
    }
}
