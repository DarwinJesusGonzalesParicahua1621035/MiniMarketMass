<?php
class AsistenciaController extends CrudController
{
    protected function config(): array
{
    $r = new AsistenciaRepository();
    return [
        'carpeta' => 'asistencia', 'titulo' => 'Asistencia', 'singular' => 'asistencia',
        'repo' => $r,
        'campos' => [
            'empleado_id' => ['label' => 'Empleado', 'tipo' => 'select', 'req' => true,
                              'opciones' => fn() => $r->pares('SELECT id, nombre FROM empleado ORDER BY nombre')],
            'entrada'     => ['label' => 'Entrada', 'tipo' => 'datetime-local', 'req' => true],
            'salida'      => ['label' => 'Salida (opcional)', 'tipo' => 'datetime-local', 'nulo' => true],
            'estado'      => ['label' => 'Estado', 'tipo' => 'select', 'req' => true,
                              'opciones' => ['AUTO' => 'Automático (según la hora de entrada)', 'FALTA' => 'Falta (no marcó asistencia)']],
        ],
    ];
}
    protected function antes(array $d, int $id): array
    {
        if ($d['entrada'] === '') {
            throw new RuntimeException('la entrada es obligatoria.');
        }
        if ($d['estado'] === 'FALTA') {
            // Falta cargada a mano: no hay entrada real, se guarda a las 00:00:00 y sin salida.
            $d['entrada'] = substr($d['entrada'], 0, 10) . ' 00:00:00';
            $d['salida']  = null;
        } else {
            if ($d['salida'] !== null && $d['salida'] < $d['entrada']) {
                throw new RuntimeException('la salida no puede ser anterior a la entrada.');
            }
            // Temprano hasta 08:30 · Tarde después de 08:30
            $d['estado'] = AsistenciaRepository::estadoPorEntrada($d['entrada']);
        }
        return $d;
    }

    // Botón "Marcar salida ahora" del drawer (solo registros de hoy, sin salida y que no sean falta)
    public function marcarsalida(): void
    {
        $repo = new AsistenciaRepository();
        $a = $repo->buscar((int)($_POST['id'] ?? 0));
        try {
            if (!$a || $a['salida'] !== null || AsistenciaRepository::sinEntrada($a)
                || substr($a['entrada'], 0, 10) !== date('Y-m-d')) {
                throw new RuntimeException('este registro no admite marcar salida.');
            }
            $repo->actualizar($a['id'], ['salida' => max(date('Y-m-d H:i:s'), $a['entrada'])]);
            $this->flash('Salida registrada');
        } catch (RuntimeException | PDOException $e) {
            $this->flash('No se pudo marcar la salida: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('asistencia/index');
    }
}