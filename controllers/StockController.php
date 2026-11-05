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
            'titulo' => 'Registrar movimiento',
            'stocks' => $r->opcionesStock(),
            'old'    => $old,
        ]);
    }

    public function guardar(): void
    {
        [$sucursalId, $productoId] = array_pad(explode('-', (string)($_POST['stock'] ?? ''), 2), 2, '0');
        try {
            $cant = trim((string)($_POST['cantidad'] ?? ''));
            if (!preg_match('/^\d{1,7}$/', $cant) || (int)$cant <= 0) {
                throw new InvalidArgumentException('La cantidad debe ser un número entero mayor que 0.');
            }
            // Las ventas las registra el punto de venta; aquí solo entradas y salidas manuales.
            if (!in_array($_POST['tipo'] ?? '', ['ENTRADA', 'SALIDA'], true)) {
                throw new InvalidArgumentException('Elige si es una entrada o una salida.');
            }
            $u = Auth::user();
            (new MovimientoStock(Database::pdo()))->registrar([
                'sucursal_id' => $sucursalId,
                'producto_id' => $productoId,
                'empleado_id' => $u['id'],
                'tipo'        => $_POST['tipo'] ?? '',
                'cantidad'    => $_POST['cantidad'] ?? '',
                'motivo'      => $_POST['motivo'] ?? '',
            ]);
            $this->flash('Movimiento registrado');
            $this->redirect('stock/movimientos');
        } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
            $_SESSION['stock_old'] = [
                'stock'    => (string)($_POST['stock'] ?? ''),
                'tipo'     => (string)($_POST['tipo'] ?? ''),
                'cantidad' => (string)($_POST['cantidad'] ?? ''),
                'motivo'   => (string)($_POST['motivo'] ?? ''),
            ];
            $this->flash('No se pudo registrar: ' . $this->mensaje($e), 'err');
            $this->redirect('stock/registrar');
        }
    }

}