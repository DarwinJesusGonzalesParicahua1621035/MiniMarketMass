<?php
class StockController extends Controller
{
    protected array $roles = ['ADMINISTRADOR', 'ALMACENERO'];

    public function index(): void
    {
        $r = new StockRepository();
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $this->render('stock/index', [
            'titulo'      => 'Stock',
            'filas'       => $r->resumen($sucursal),
            'sucursales'  => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre'),
            'sucursalSel' => $sucursal,
            'faltantes'   => $r->faltantes(),
        ]);
    }

    public function movimientos(): void
    {
        $r = new StockRepository();
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $tipo = strtoupper(trim($_GET['tipo'] ?? ''));
        if (!in_array($tipo, ['ENTRADA', 'SALIDA', 'AJUSTE', 'MERMA'], true)) {
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
            'combos' => $r->combinaciones(),
            'old'    => $old,
        ]);
    }

    public function guardar(): void
    {
        [$sucursalId, $productoId] = array_pad(explode('-', (string)($_POST['stock'] ?? ''), 2), 2, '0');
        try {
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

    public function inicializar(): void
    {
        [$sucursalId, $productoId] = array_pad(explode('-', (string)($_POST['faltante'] ?? ''), 2), 2, '0');
        try {
            $creado = (new Stock(Database::pdo()))->inicializar((int)$sucursalId, (int)$productoId);
            $this->flash($creado ? 'Stock inicializado en 0' : 'Ese producto ya tenía stock');
        } catch (PDOException $e) {
            $this->flash('No se pudo inicializar: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('stock/index');
    }
}