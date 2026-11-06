<?php
class StockController extends Controller
{
    protected array $roles = ['ADMINISTRADOR', 'ALMACENERO'];

    public function index(): void
    {
        $r = new StockRepository();
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $sucursales = $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre');
        if ($sucursal && !isset($sucursales[$sucursal])) {
            $sucursal = null;
        }
        $this->render('stock/index', [
            'titulo'      => 'Stock',
            'inventario'  => $r->inventario(),
            'sucursales'  => $sucursales,
            'sucursalSel' => $sucursal,
        ]);
    }

    public function movimientos(): void
    {
        $r = new StockRepository();
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $tipo = strtoupper(trim($_GET['tipo'] ?? ''));
        if (!in_array($tipo, ['ENTRADA', 'SALIDA', 'VENTA'], true)) {
            $tipo = null;
        }
        $this->render('stock/movimientos', [
            'titulo'      => 'Movimientos de stock',
            'filas'       => $r->historial($sucursal, $tipo),
            'sucursales'  => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre'),
            'sucursalSel' => $sucursal,
            'tipoSel'     => $tipo,
        ]);
    }

    public function registrar(): void
    {
        $r = new StockRepository();
        // Datos escritos antes de un error (se muestran una sola vez).
        $old = $_SESSION['stock_old'] ?? [];
        unset($_SESSION['stock_old']);
        $this->render('stock/registrar', [
            'titulo'     => 'Registrar movimiento',
            'stocks'     => $r->opcionesStock(),
            'categorias' => $r->pares('SELECT id, nombre FROM categoria ORDER BY nombre'),
            'motivos'    => $r->motivosRecientes(),
            'old'        => $old,
        ]);
    }

    /**
     * Guarda UN movimiento con VARIOS productos de la misma sucursal.
     * Todo o nada: si una línea falla (por ejemplo stock insuficiente), no se guarda ninguna.
     * POST: sucursal, tipo (ENTRADA|SALIDA), motivo, items[producto_id] = cantidad
     */
    public function guardar(): void
    {
        $sucursalId = (string)($_POST['sucursal'] ?? '');
        $tipo       = (string)($_POST['tipo'] ?? '');
        $motivo     = trim((string)($_POST['motivo'] ?? ''));
        $items      = is_array($_POST['items'] ?? null) ? $_POST['items'] : [];
        $db = null;
        try {
            if (!preg_match('/^\d+$/', $sucursalId) || (int)$sucursalId <= 0) {
                throw new InvalidArgumentException('Elige una sucursal.');
            }
            // Las ventas las registra el punto de venta; aquí solo entradas y salidas manuales.
            if (!in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
                throw new InvalidArgumentException('Elige si es una entrada o una salida.');
            }
            if (!$items) {
                throw new InvalidArgumentException('Agrega al menos un producto.');
            }
            if (count($items) > 100) {
                throw new InvalidArgumentException('Máximo 100 productos por movimiento.');
            }

            $lineas = [];
            foreach ($items as $pid => $cant) {
                $cant = trim((string)$cant);
                if (!preg_match('/^\d+$/', (string)$pid) || (int)$pid <= 0) {
                    throw new InvalidArgumentException('Hay un producto inválido en la lista.');
                }
                if (!preg_match('/^\d{1,7}$/', $cant) || (int)$cant <= 0) {
                    throw new InvalidArgumentException('Cada cantidad debe ser un número entero mayor que 0.');
                }
                $lineas[(int)$pid] = $cant;
            }
            ksort($lineas);   // orden fijo: evita bloqueos cruzados si dos usuarios guardan a la vez

            $u   = Auth::user();
            $db  = Database::pdo();
            $mov = new MovimientoStock($db);

            // MovimientoStock::registrar() se une a esta transacción en vez de abrir otra.
            $db->beginTransaction();
            foreach ($lineas as $pid => $cant) {
                $mov->registrar([
                    'sucursal_id' => $sucursalId,
                    'producto_id' => $pid,
                    'empleado_id' => $u['id'],
                    'tipo'        => $tipo,
                    'cantidad'    => $cant,
                    'motivo'      => $motivo,
                ]);
            }
            $db->commit();

            $n = count($lineas);
            $this->flash($n === 1 ? 'Movimiento registrado' : "Movimiento registrado: $n productos actualizados");
            $this->redirect('stock/movimientos');
        } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['stock_old'] = [
                'sucursal' => $sucursalId,
                'tipo'     => $tipo,
                'motivo'   => $motivo,
                'items'    => array_map('strval', $items),
            ];
            $this->flash('No se pudo registrar (no se guardó ningún producto): ' . $this->mensaje($e), 'err');
            $this->redirect('stock/registrar');
        }
    }

}
