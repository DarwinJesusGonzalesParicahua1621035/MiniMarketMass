<?php
class VentasController extends Controller
{
    public function index(): void
    {
        $c = [
            'carpeta' => 'ventas', 'titulo' => 'Ventas', 'acciones' => ['ver'],
            'columnas' => [
                'id' => ['#'], 'sucursal' => ['Sucursal'], 'tipo' => ['Tipo', 'etiqueta'], 'comprobante' => ['Comprobante'],
                'cliente' => ['Cliente'], 'fecha' => ['Fecha'], 'subtotal' => ['Subtotal', 'dinero'], 'igv' => ['IGV', 'dinero'],
                'total' => ['Total', 'dinero'], 'estado' => ['Estado', 'etiqueta'],
            ],
        ];
        $this->render('ventas/index', ['c' => $c, 'filas' => (new VentaRepository())->todos(), 'titulo' => 'Ventas']);
    }

    public function ver(): void
    {
        $r = new VentaRepository();
        $id = (int)($_GET['id'] ?? 0);
        $v = $r->venta($id);
        if (!$v) {
            $this->redirect('ventas/index');
        }
        $this->render('ventas/ver', ['v' => $v, 'detalle' => $r->detalle($id), 'pagos' => $r->pagos($id), 'titulo' => 'Comprobante ' . $v['comprobante']]);
    }

    public function anular(): void
    {
        $id = (int)($_POST['id'] ?? 0);
        $motivo = trim($_POST['motivo'] ?? '');
        if ($motivo === '') {
            $this->flash('Indica el motivo de anulación.', 'err');
        } elseif ((new VentaRepository())->anular($id, mb_substr($motivo, 0, 150))) {
            $this->flash('Comprobante anulado');
        } else {
            $this->flash('No se pudo anular el comprobante.', 'err');
        }
        $this->redirect('ventas/ver', ['id' => $id]);
    }
}