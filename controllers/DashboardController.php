<?php
class DashboardController extends Controller
{
    public function index(): void
    {
        $r = new ResumenRepository();
        $map = [];
        foreach ($r->semana() as $f) {
            $map[$f['dia']] = (float)$f['total'];
        }
        $dias = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $semana = [];
        for ($i = 6; $i >= 0; $i--) {
            $t = strtotime("-$i day");
            $semana[] = [$dias[date('w', $t)], $map[date('Y-m-d', $t)] ?? 0];
        }
        $this->render('dashboard/index', [
            'titulo' => 'Resumen del día', 'k' => $r->kpis(), 'semana' => $semana,
            'medios' => $r->medios(), 'ultimas' => $r->ultimas(),
        ]);
    }
}