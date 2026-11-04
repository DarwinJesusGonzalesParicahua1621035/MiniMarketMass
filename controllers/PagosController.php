<?php
class PagosController extends Controller
{
    public function index(): void
    {
        $medio = strtoupper(trim($_GET['medio'] ?? ''));
        if (!in_array($medio, PagoRepository::MEDIOS, true)) {
            $medio = null;
        }
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $hasta = $this->fecha($_GET['hasta'] ?? '') ?? date('Y-m-d');
        $desde = $this->fecha($_GET['desde'] ?? '') ?? date('Y-m-d', strtotime($hasta . ' -6 days'));
        if ($desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        $r = new PagoRepository();
        $this->render('pagos/index', [
            'titulo'      => 'Pagos',
            'filas'       => $r->listado($medio, $sucursal, $desde, $hasta),
            'totales'     => $r->totales($medio, $sucursal, $desde, $hasta),
            'sucursales'  => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre'),
            'medioSel'    => $medio,
            'sucursalSel' => $sucursal,
            'desde'       => $desde,
            'hasta'       => $hasta,
        ]);
    }

    private function fecha(string $v): ?string
    {
        $d = DateTime::createFromFormat('!Y-m-d', $v);
        return $d && $d->format('Y-m-d') === $v ? $v : null;
    }
}
