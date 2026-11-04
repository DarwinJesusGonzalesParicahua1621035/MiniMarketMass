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

    // Mi caja: según la etapa del turno muestra asistencia, apertura de caja o el resumen.
    public function caja(): void
    {
        $b = $this->contexto();
        $uid = (int)Auth::user()['id'];
        $datos = ['titulo' => 'Mi caja'] + $b;
        if ($t = $b['turno']) {
            $pos = new PosRepository();
            $datos['resumen'] = $pos->resumenTurno((int)$t['id']);
            $datos['ventas']  = $pos->ventasTurno((int)$t['id']);
        } else {
            $datos['asistencia'] = (new AsistenciaRepository())->deHoy($uid);
            $datos['cajas'] = $datos['asistencia'] ? (new CajaRepository())->delEmpleado($uid) : [];
        }
        $this->render('cajero/caja', $datos, false);
    }

    // Etapa 1 del turno: el cajero marca su entrada.
    public function asistencia(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new AsistenciaRepository())->registrarEntrada((int)Auth::user()['id']);
                $this->flash('Asistencia registrada');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo registrar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('cajero/caja');
    }

    // Reportar una incidencia (solo con la caja abierta). Se guarda en la sucursal del turno.
    public function incidencia(): void
    {
        $volver = (string)($_POST['volver'] ?? '');
        if (!in_array($volver, ['cajero/venta', 'cajero/ventas', 'cajero/caja', 'cajero/clientes'], true)) {
            $volver = 'cajero/caja';
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $b = $this->contexto();
            $tipo = (string)($_POST['tipo'] ?? '');
            $desc = trim((string)($_POST['descripcion'] ?? ''));
            try {
                if (!$b['turno']) {
                    throw new RuntimeException('abre una caja para reportar incidencias.');
                }
                if (!in_array($tipo, ['Equipos', 'Seguridad', 'Stock', 'Otro'], true)) {
                    throw new InvalidArgumentException('elige el tipo de incidencia.');
                }
                if ($desc === '' || mb_strlen($desc) > 250) {
                    throw new InvalidArgumentException('cuéntanos qué pasó (máximo 250 caracteres).');
                }
                (new IncidenteRepository())->insertar([
                    'sucursal_id' => (int)$b['turno']['sucursal_id'],
                    'tipo'        => $tipo,
                    'descripcion' => $desc,
                    'fecha'       => date('Y-m-d H:i:s'),
                    'estado'      => 'ABIERTO',
                ]);
                $this->flash('Incidencia enviada al administrador');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo enviar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect($volver);
    }

    // Mis ventas: comprobantes del cajero en un día.
    public function ventas(): void
    {
        $b = $this->contexto();
        $f = (string)($_GET['fecha'] ?? '');
        $d = DateTime::createFromFormat('Y-m-d', $f);
        $dia = ($d && $d->format('Y-m-d') === $f) ? $f : date('Y-m-d');
        $filas = (new PosRepository())->misVentas((int)Auth::user()['id'], $dia);
        $this->render('cajero/ventas', [
            'titulo' => 'Mis ventas', 'dia' => $dia, 'filas' => $filas,
            'urlDetalle' => self::url('cajero/detalle'),
        ] + $b, false);
    }

    // Detalle de una venta para el ticket (responde JSON).
    public function detalle(): void
    {
        $u = Auth::user();
        $ventas = new VentaRepository();
        $v = $ventas->venta((int)($_GET['id'] ?? 0));
        if (!$v || ($u['rol'] !== 'ADMINISTRADOR' && (int)$v['empleado_id'] !== (int)$u['id'])) {
            $this->json(['ok' => false, 'error' => 'Venta no encontrada.'], 404);
        }
        $c = $v['cliente_id'] ? (new ClienteRepository())->buscar($v['cliente_id']) : null;
        $this->json([
            'ok' => true,
            'venta' => [
                'tipo' => $v['tipo_comprobante'], 'comprobante' => $v['comprobante'], 'fecha' => $v['fecha'],
                'estado' => $v['estado'], 'cliente' => $v['cliente'],
                'doc' => $c ? $c['tipo_documento'] . ' ' . $c['numero_documento'] : '',
                'subtotal' => (float)$v['subtotal'], 'igv' => (float)$v['igv'], 'total' => (float)$v['total'],
                'cajero' => $v['cajero'], 'sucursal' => $v['sucursal'],
            ],
            'items' => $ventas->detalle((int)$v['id']),
            'pagos' => $ventas->pagos((int)$v['id']),
        ]);
    }

    // Clientes: consulta por nombre, DNI o RUC.
    public function clientes(): void
    {
        $b = $this->contexto();
        $q = trim((string)($_GET['q'] ?? ''));
        $this->render('cajero/clientes', [
            'titulo' => 'Clientes', 'q' => $q, 'filas' => (new PosRepository())->clientes($q),
        ] + $b, false);
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
        } catch (Throwable $e) {
            $this->json(['ok' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
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
        if (ob_get_length()) { ob_clean(); }
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}