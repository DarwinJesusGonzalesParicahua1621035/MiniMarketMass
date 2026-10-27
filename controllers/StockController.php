<?php
class StockController extends Controller
{
    public function index(): void
    {
        $c = [
            'carpeta' => 'stock', 'titulo' => 'Stock por sucursal', 'nuevo' => 'stock/nuevo',
            'extra' => [['Ver movimientos', 'stock/movimientos']],
            'columnas' => ['sucursal' => ['Sucursal'], 'producto' => ['Producto'], 'cantidad' => ['Cantidad', 'stock']],
        ];
        $this->render('stock/index', ['c' => $c, 'filas' => (new StockRepository())->todos(), 'titulo' => $c['titulo']]);
    }

    public function movimientos(): void
    {
        $c = [
            'carpeta' => 'stock', 'titulo' => 'Movimientos de stock', 'extra' => [['← Stock', 'stock/index']],
            'columnas' => [
                'id' => ['#'], 'sucursal' => ['Sucursal'], 'producto' => ['Producto'], 'tipo' => ['Tipo', 'etiqueta'],
                'cantidad' => ['Cantidad'], 'motivo' => ['Motivo'], 'fecha' => ['Fecha'],
            ],
        ];
        $this->render('stock/movimientos', ['c' => $c, 'filas' => (new StockRepository())->movimientos(), 'titulo' => $c['titulo']]);
    }

    public function nuevo(): void
    {
        $r = new StockRepository();
        $c = [
            'carpeta' => 'stock',
            'campos' => [
                'sucursal_id' => ['label' => 'Sucursal', 'tipo' => 'select', 'req' => true, 'opciones' => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre')],
                'producto_id' => ['label' => 'Producto', 'tipo' => 'select', 'req' => true, 'opciones' => $r->pares('SELECT id, nombre FROM producto ORDER BY nombre')],
                'tipo'        => ['label' => 'Tipo (ENTRADA y AJUSTE suman; SALIDA y MERMA restan)', 'tipo' => 'select', 'req' => true,
                                  'opciones' => ['ENTRADA' => 'ENTRADA', 'SALIDA' => 'SALIDA', 'MERMA' => 'MERMA', 'AJUSTE' => 'AJUSTE']],
                'cantidad'    => ['label' => 'Cantidad', 'tipo' => 'number', 'req' => true, 'step' => '0.001', 'attr' => 'min="0.001"'],
                'motivo'      => ['label' => 'Motivo (opcional)', 'tipo' => 'text', 'max' => 100],
            ],
        ];
        $this->render('stock/form', ['c' => $c, 'reg' => [], 'id' => 0, 'titulo' => 'Registrar movimiento']);
    }

    public function guardar(): void
    {
        $tipo = $_POST['tipo'] ?? '';
        $cant = (float)($_POST['cantidad'] ?? 0);
        try {
            if ($cant <= 0 || !in_array($tipo, ['ENTRADA', 'SALIDA', 'MERMA', 'AJUSTE'], true)) {
                throw new RuntimeException('datos de movimiento no válidos.');
            }
            (new StockRepository())->registrar(
                (int)($_POST['sucursal_id'] ?? 0), (int)($_POST['producto_id'] ?? 0), $tipo, $cant,
                trim($_POST['motivo'] ?? '') ?: null, Auth::user()['id']
            );
            $this->flash('Movimiento registrado');
        } catch (PDOException | RuntimeException $e) {
            $this->flash('No se pudo registrar: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('stock/index');
    }
}