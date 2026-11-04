<?php
class VentasController extends Controller
{
    public function index(): void
    {
        $r = new VentaRepository();
        $turno = (int)($_GET['turno'] ?? 0);
        $dia = $_GET['fecha'] ?? '';
        $d = DateTime::createFromFormat('Y-m-d', $dia);
        if (!$d || $d->format('Y-m-d') !== $dia) {
            $dia = ($turno ? $r->diaDeTurno($turno) : null) ?: $r->ultimoDia();
        }
        $f = [
            'dia' => $dia,
            'turno' => $turno,
            'todos' => !empty($_GET['todos']) || $turno > 0,
            'q' => mb_substr(trim($_GET['q'] ?? ''), 0, 60),
            'sucursal' => (int)($_GET['sucursal'] ?? 0),
            // Filtros retirados de la pantalla: quedan vacíos para el repositorio.
            'tipo' => '', 'medio' => '', 'caja' => 0, 'min' => null, 'max' => null,
            'orden' => ($_GET['orden'] ?? '') === 'total' ? 'total' : 'fecha',
            'dir' => ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
        ];
        $this->render('ventas/index', [
            'f' => $f, 'rep' => $r->reporte($dia), 'op' => $r->opciones(),
            'filas' => $r->listado($f), 'titulo' => 'Ventas',
        ]);
    }

    public function detalle(): void
    {
        $v = (new VentaRepository())->ficha((int)($_GET['id'] ?? 0));
        header('Content-Type: application/json; charset=utf-8');
        if (!$v) {
            http_response_code(404);
            exit('{}');
        }
        exit(json_encode($v, JSON_UNESCAPED_UNICODE));
    }

    public function ver(): void
    {
        $r = new VentaRepository();
        $id = (int)($_GET['id'] ?? 0);
        $v = $r->venta($id);
        if (!$v) {
            $this->redirect('ventas/index');
        }
        $this->render('ventas/ver', [
            'v' => $v, 'detalle' => $r->detalle($id), 'pagos' => $r->pagos($id),
            'titulo' => 'Comprobante ' . $v['comprobante'],
        ]);
    }

    public function anular(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        try {
            if ($motivo === '') {
                throw new RuntimeException('indica el motivo de anulación.');
            }
            (new VentaRepository())->anular($id, mb_substr($motivo, 0, 150), Auth::user()['id']);
            $this->flash('Comprobante anulado y stock devuelto');
        } catch (Throwable $e) {
            $this->flash('No se pudo anular: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('ventas/ver', ['id' => $id]);
    }
}