<?php
class HistoriaCajaController
{
    const ACCIONES = ['index', 'abrir', 'cerrar'];
    private HistoriaCaja $modelo;

    public function __construct(private PDO $db) { $this->modelo = new HistoriaCaja($db); }

    public function index(): void
    {
        $estado = $_GET['estado'] ?? '';
        if (!in_array($estado, ['', 'ABIERTA', 'CERRADA'], true)) {
            $estado = '';
        }
        $historial = $this->modelo->listar($estado);
        $cajas = $this->modelo->cajas();
        $empleados = $this->modelo->empleados();
        $this->vista('caja/historial', compact('historial', 'cajas', 'empleados', 'estado'));
    }

    public function abrir(): void
    {
        $this->soloPost();
        $cajaId = (int)($_POST['caja_id'] ?? 0);
        $empleadoId = (int)($_POST['empleado_id'] ?? 0);
        $monto = trim($_POST['monto_inicial'] ?? '');
        if ($cajaId <= 0 || $empleadoId <= 0 || !$this->montoValido($monto)) {
            flash('err', 'Selecciona una caja y un empleado; el monto inicial debe ser un número no negativo de hasta dos decimales.');
            redirigir('productos.php?m=historia_caja');
        }

        try {
            $this->modelo->abrir($cajaId, $empleadoId, number_format((float)$monto, 2, '.', ''));
            flash('ok', 'Caja abierta correctamente.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'No se pudo registrar la apertura.'));
        } catch (RuntimeException $ex) {
            flash('err', $ex->getMessage());
        }
        redirigir('productos.php?m=historia_caja');
    }

    public function cerrar(): void
    {
        $this->soloPost();
        $id = (int)($_POST['id'] ?? 0);
        $monto = trim($_POST['monto_final'] ?? '');
        if ($id <= 0 || !$this->montoValido($monto)) {
            flash('err', 'El monto final debe ser un número no negativo de hasta dos decimales.');
            redirigir('productos.php?m=historia_caja');
        }

        try {
            $this->modelo->cerrar($id, number_format((float)$monto, 2, '.', ''));
            flash('ok', 'Caja cerrada correctamente.');
        } catch (PDOException $ex) {
            flash('err', mensaje_error($ex, 'No se pudo registrar el cierre.'));
        } catch (RuntimeException $ex) {
            flash('err', $ex->getMessage());
        }
        redirigir('productos.php?m=historia_caja');
    }

    private function montoValido(string $monto): bool
    {
        return preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $monto) === 1;
    }

    private function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método no permitido'); }
    }

    private function vista(string $archivo, array $datos = []): void
    {
        extract($datos);
        pagina_inicio('Historial de caja');
        require __DIR__ . '/../views/' . $archivo . '.php';
        pagina_fin();
    }
}
