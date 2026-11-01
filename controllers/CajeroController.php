<?php
class CajeroController extends Controller
{
    protected array $roles = ['CAJERO', 'ADMINISTRADOR'];

    // Al entrar, el cajero va directo a la venta.
    public function index(): void
    {
        $this->redirect('cajero/venta');
    }

    // Nueva venta (punto de venta).
    public function venta(): void
    {
        $b = $this->contexto();
        $cfg = null;
        if ($t = $b['turno']) {
            $pos = new PosRepository();
            $sid = (int)$t['sucursal_id'];
            $cfg = [
                'productos' => $pos->productos($sid),
                'serieB'    => $t['serie_boleta'],
                'serieF'    => $t['serie_factura'],
                'siguiente' => [
                    'BOLETA'  => $pos->siguiente($sid, $t['serie_boleta']),
                    'FACTURA' => $pos->siguiente($sid, $t['serie_factura']),
                ],
                'urlCobrar'  => self::url('cajero/cobrar'),
                'urlCliente' => self::url('cajero/cliente'),
                'csrf'       => self::csrf(),
            ];
        }
        $this->render('cajero/venta', ['titulo' => 'Nueva venta', 'cfg' => $cfg] + $b, false);
    }

     // Mi caja: estado del turno, resumen de ventas y cierre.
    public function caja(): void
    {
        $b = $this->contexto();
        $datos = ['titulo' => 'Mi caja'] + $b;
        if ($t = $b['turno']) {
            $pos = new PosRepository();
            $datos['resumen'] = $pos->resumenTurno((int)$t['id']);
            $datos['ventas']  = $pos->ventasTurno((int)$t['id']);
        } else {
            $datos['cajas'] = (new CajaRepository())->libres();
        }
        $this->render('cajero/caja', $datos, false);
    }

    // Busca un cliente por DNI o RUC (responde JSON).
    
    public function cliente(): void
    {
        $doc = preg_replace('/\D/', '', (string)($_GET['doc'] ?? ''));
        $c = $doc !== '' ? (new PosRepository())->cliente($doc) : null;
        $this->json($c ? ['found' => true, 'nombre' => $c['nombre']] : ['found' => false]);
    }

    // Registra la venta (responde JSON).
    public function cobrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Método no permitido.'], 405);
        }
        $u = Auth::user();
        $pos = new PosRepository();
        try {
            $t = $pos->turno((int)$u['id']);
            if (!$t) {
                throw new RuntimeException('Tu caja está cerrada. Ábrela para vender.');
            }
            $d = json_decode((string)($_POST['datos'] ?? ''), true);
            if (!is_array($d)) {
                throw new InvalidArgumentException('Datos de venta inválidos.');
            }
            $r = $pos->registrar($t, (int)$u['id'], $d);

            $ventas = new VentaRepository();
            $v = $ventas->venta($r['id']);
            $c = $v['cliente_id'] ? (new ClienteRepository())->buscar($v['cliente_id']) : null;
            $this->json([
                'ok' => true,
                'venta' => [
                    'tipo' => $v['tipo_comprobante'], 'comprobante' => $v['comprobante'], 'fecha' => $v['fecha'],
                    'cliente' => $v['cliente'], 'doc' => $c ? $c['tipo_documento'] . ' ' . $c['numero_documento'] : '',
                    'subtotal' => (float)$v['subtotal'], 'igv' => (float)$v['igv'], 'total' => (float)$v['total'],
                    'cajero' => $v['cajero'], 'caja' => $t['caja'], 'sucursal' => $t['sucursal'], 'direccion' => $t['direccion'],
                ],
                'items' => $ventas->detalle($r['id']),
                'pagos' => $ventas->pagos($r['id']),
                'recibido' => $r['recibido'],
                'vuelto' => $r['vuelto'],
                'siguiente' => [
                    'BOLETA'  => $pos->siguiente((int)$t['sucursal_id'], $t['serie_boleta']),
                    'FACTURA' => $pos->siguiente((int)$t['sucursal_id'], $t['serie_factura']),
                ],
            ]);
        } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
            $this->json(['ok' => false, 'error' => $this->mensaje($e)], 422);
        }
    }

    // Datos comunes a las pantallas del cajero.
    private function contexto(): array
    {
        $pos = new PosRepository();
        $id = (int)Auth::user()['id'];
        $turno = $pos->turno($id);
        return ['turno' => $turno, 'sucursal' => $turno['sucursal'] ?? $pos->sucursalDe($id)];
    }

    private function json(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}