<?php
/**
 * Panel de Configuración (admin).
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class ConfiguracionController extends Controller
{
    public function index(): void
    {
        $sucRepo = new SucursalRepository();
        $incRepo = new IncidenteRepository();
        $sucursales = $sucRepo->todos();
        $incidentes = $incRepo->todos();

        $abiertos = 0;
        $tarde = 0;
        $ahora = time();
        foreach ($incidentes as $i) {
            if (($i['estado'] ?? '') !== 'ABIERTO') {
                continue;
            }
            $abiertos++;
            if ($ahora - strtotime($i['fecha']) > 86400) {
                $tarde++;
            }
        }

        $distritos = [];
        $cajas = 0;
        foreach ($sucursales as $s) {
            $distritos[$s['distrito']] = true;
            $cajas += (int)$s['cajas'];
        }

        $this->render('configuracion/index', [
            'titulo' => 'Configuración',
            'nSuc' => count($sucursales),
            'nDist' => count($distritos),
            'nCajas' => $cajas,
            'nInc' => count($incidentes),
            'nAbiertos' => $abiertos,
            'nTarde' => $tarde,
        ]);
    }
}
