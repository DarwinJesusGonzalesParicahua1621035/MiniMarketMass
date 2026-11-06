<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $r = new ResumenRepository();
        $sucursales = [];
        foreach ($r->sucursales() as $s) {
            $sucursales[(int)$s['id']] = $s['nombre'];
        }

        // Ventas por día y por sucursal: $map[fecha][sucursal_id] = total
        $map = [];
        foreach ($r->semana() as $f) {
            $map[$f['dia']][(int)$f['sucursal_id']] = (float)$f['total'];
        }
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $semana = [];
        for ($i = 6; $i >= 0; $i--) {
            $t = strtotime("-$i day");
            $fila = [];
            foreach ($sucursales as $id => $_) {
                $fila[$id] = $map[date('Y-m-d', $t)][$id] ?? 0;
            }
            $semana[] = ['dia' => $dias[date('w', $t)], 'ventas' => $fila];
        }

        // Medios de pago por sucursal: $medios[sucursal_id][MEDIO] = total
        $medios = [];
        foreach ($r->medios() as $m) {
            $medios[(int)$m['sucursal_id']][$m['medio']] = (float)$m['total'];
        }

        $this->render('dashboard/index', [
            'titulo'     => 'Resumen del día',
            'k'          => $r->kpis(),
            'sucursales' => $sucursales,
            'semana'     => $semana,
            'medios'     => $medios,
            'sedes'      => $r->sucursalesHoy(),
            'stockBajo'  => $r->stockBajo(),
            'incidentes' => $r->incidentesAbiertos(),
            'top'        => $r->masVendidos(),
        ]);
    }
}