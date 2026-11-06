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
        $reporte = null;
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
            $reporte = $this->reporte($t, $cfg['productos']);
        }
        $this->render('cajero/venta', ['titulo' => 'Nueva venta', 'cfg' => $cfg, 'reporte' => $reporte] + $b, false);
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
            $datos['reporte'] = $this->reporte($t, $pos->productos((int)$t['sucursal_id']));
        } else {
            $datos['asistencia'] = (new AsistenciaRepository())->deHoy($uid);
            $datos['cajas'] = ($datos['asistencia'] && !$datos['asistencia']['salida']) ? (new CajaRepository())->delEmpleado($uid) : [];
        }
        $this->render('cajero/caja', $datos, false);
    }

    // Etapa 1 del turno: el cajero marca su entrada.
    public function asistencia(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $repo = new AsistenciaRepository();
                $repo->registrarEntrada((int)Auth::user()['id']);
                $a = $repo->deHoy((int)Auth::user()['id']);
                $this->flash('Asistencia registrada · ' . AsistenciaRepository::etiqueta($a['estado'] ?? 'PRESENTE'));
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo registrar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('cajero/caja');
    }

    // Fin del turno: el cajero marca su salida (con la caja cerrada).
    public function salida(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new AsistenciaRepository())->registrarSalida((int)Auth::user()['id']);
                $this->flash('Turno terminado. ¡Hasta mañana!');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo terminar el turno: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('cajero/caja');
    }

    // Reportar una incidencia (solo con la caja abierta). Se guarda en la sucursal del turno.
    public function incidencia(): void
    {
        $volver = (string)($_POST['volver'] ?? '');
        if (!in_array($volver, ['cajero/venta', 'cajero/caja', 'cajero/clientes'], true)) {
            $volver = 'cajero/caja';
        }
        $ajax = ($_POST['ajax'] ?? '') === '1';
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
                $id = (new IncidenteRepository())->insertar([
                    'sucursal_id' => (int)$b['turno']['sucursal_id'],
                    'tipo'        => $tipo,
                    'descripcion' => $desc,
                    'fecha'       => date('Y-m-d H:i:s'),
                    'estado'      => 'ABIERTO',
                ]);
                if ($ajax) {
                    $this->json(['ok' => true, 'id' => $id, 'mensaje' => 'Incidencia enviada al administrador.']);
                }
                $this->flash('Incidencia enviada al administrador');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                if ($ajax) {
                    $this->json(['ok' => false, 'error' => 'No se pudo enviar: ' . $this->mensaje($e)], 422);
                }
                $this->flash('No se pudo enviar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect($volver);
    }

    // Reporta stock del turno: merma o ajuste por conteo, con varios productos a la vez.
    public function stock(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'Método no permitido.'], 405);
        }
        $db = null;
        try {
            $u = Auth::user();
            $t = (new PosRepository())->turno((int)$u['id']);
            if (!$t) {
                throw new RuntimeException('abre una caja para reportar stock.');
            }
            $d = json_decode((string)($_POST['datos'] ?? ''), true);
            if (!is_array($d)) {
                throw new InvalidArgumentException('datos del reporte inválidos.');
            }
            $accion = strtoupper(trim((string)($d['accion'] ?? '')));
            if (!in_array($accion, ['MERMA', 'AJUSTE'], true)) {
                throw new InvalidArgumentException('elige merma o ajuste de stock.');
            }
            $motivo = trim((string)($d['motivo'] ?? ''));
            if (mb_strlen($motivo) > 70) {
                throw new InvalidArgumentException('el motivo no puede pasar de 70 caracteres.');
            }
            $items = $d['items'] ?? null;
            if (!is_array($items) || !$items) {
                throw new InvalidArgumentException('agrega al menos un producto al reporte.');
            }
            if (count($items) > 20) {
                throw new InvalidArgumentException('puedes reportar hasta 20 productos a la vez.');
            }

            $lineas = [];
            foreach ($items as $it) {
                if (!is_array($it)) {
                    throw new InvalidArgumentException('hay una línea inválida en el reporte.');
                }
                $pid = (int)($it['producto_id'] ?? 0);
                if ($pid <= 0) {
                    throw new InvalidArgumentException('elige un producto en todas las líneas.');
                }
                if (isset($lineas[$pid])) {
                    throw new InvalidArgumentException('hay un producto repetido en el reporte.');
                }
                $cantidad = str_replace(',', '.', trim((string)($it['cantidad'] ?? '')));
                if (!preg_match('/^\d{1,7}(\.\d{1,3})?$/', $cantidad) || (float)$cantidad <= 0) {
                    throw new InvalidArgumentException('cada cantidad debe ser mayor que 0 (hasta 3 decimales).');
                }
                $lineas[$pid] = (float)$cantidad;
            }
            ksort($lineas); // el stock se bloquea siempre en el mismo orden

            $sid = (int)$t['sucursal_id'];
            $db = Database::pdo();
            $db->beginTransaction();

            $ids = array_keys($lineas);
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $s = $db->prepare("SELECT st.producto_id, st.cantidad, p.nombre, p.unidad
                FROM stock st JOIN producto p ON p.id = st.producto_id
                WHERE st.sucursal_id = ? AND st.producto_id IN ($ph)
                ORDER BY st.producto_id FOR UPDATE");
            $s->execute([$sid, ...$ids]);
            $existentes = [];
            foreach ($s->fetchAll() as $f) {
                $existentes[(int)$f['producto_id']] = $f;
            }
            foreach ($ids as $pid) {
                if (!isset($existentes[$pid])) {
                    throw new RuntimeException('un producto del reporte no está asignado a tu sucursal.');
                }
            }

            $mov = new MovimientoStock($db);
            $res = [];
            $cambios = 0;
            foreach ($lineas as $pid => $cantidad) {
                $fila = $existentes[$pid];
                $actual = (float)$fila['cantidad'];
                $unidad = $fila['unidad'];
                $uTxt = fn(float $n): string => $unidad === 'unidad'
                    ? (abs($n - 1) < 0.0005 ? 'unidad' : 'unidades')
                    : $unidad;

                if ($accion === 'MERMA') {
                    if ($cantidad > $actual) {
                        throw new InvalidArgumentException('solo hay ' . num($actual) . ' ' . $uTxt($actual)
                            . ' de ' . $fila['nombre'] . '.');
                    }
                    $tipo = 'SALIDA';
                    $movimiento = $cantidad;
                    $nuevo = $actual - $cantidad;
                    $detalle = 'Merma' . ($motivo !== '' ? ': ' . $motivo : '');
                } else {
                    $dif = round($cantidad - $actual, 3);
                    if (abs($dif) < 0.0005) {
                        $res[] = [
                            'producto_id' => $pid, 'nombre' => $fila['nombre'],
                            'unidad' => $unidad, 'stock' => round($actual, 3), 'cambiado' => false,
                        ];
                        continue;
                    }
                    $tipo = $dif > 0 ? 'ENTRADA' : 'SALIDA';
                    $movimiento = abs($dif);
                    $nuevo = $cantidad;
                    $detalle = 'Ajuste de stock' . ($motivo !== '' ? ': ' . $motivo : '')
                        . ' (' . ($dif > 0 ? '+' : '-') . num(abs($dif)) . ')';
                }

                $mov->registrar([
                    'sucursal_id' => $sid,
                    'producto_id' => $pid,
                    'empleado_id' => (int)$u['id'],
                    'tipo'        => $tipo,
                    'cantidad'    => $movimiento,
                    'motivo'      => mb_substr($detalle, 0, 100),
                ]);
                $cambios++;
                $res[] = [
                    'producto_id' => $pid, 'nombre' => $fila['nombre'],
                    'unidad' => $unidad, 'stock' => round($nuevo, 3), 'cambiado' => true,
                ];
            }

            if (!$cambios) {
                throw new InvalidArgumentException('las cantidades contadas coinciden con el stock actual; no hay nada que ajustar.');
            }
            $db->commit();

            $this->json([
                'ok'      => true,
                'items'   => $res,
                'mensaje' => $accion === 'MERMA'
                    ? 'Merma registrada en ' . $cambios . ($cambios === 1 ? ' producto.' : ' productos.')
                    : 'Ajuste registrado en ' . $cambios . ($cambios === 1 ? ' producto.' : ' productos.'),
            ]);
        } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['ok' => false, 'error' => 'No se pudo registrar: ' . $this->mensaje($e)], 422);
        } catch (Throwable $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->json(['ok' => false, 'error' => 'Error interno: ' . $e->getMessage()], 500);
        }
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

    // Configuración de las ventanas de reportes (incidencias y merma / ajuste).
    private function reporte(array $turno, array $productos): array
    {
        return [
            'urlIncidencia' => self::url('cajero/incidencia'),
            'urlStock'      => self::url('cajero/stock'),
            'csrf'          => self::csrf(),
            'sucursal'      => (string)($turno['sucursal'] ?? ''),
            'caja'          => (string)($turno['caja'] ?? ''),
            'productos'     => array_map(fn($p) => [
                'id' => (int)$p['id'], 'nombre' => $p['nombre'], 'categoria' => $p['categoria'],
                'unidad' => $p['unidad'], 'stock' => (float)$p['stock'],
            ], $productos),
            'tipos'         => ['Equipos', 'Seguridad', 'Stock', 'Otro'],
        ];
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