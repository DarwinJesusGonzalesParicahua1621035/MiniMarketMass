<?php
class AsistenciaController extends CrudController
{
    protected function config(): array
    {
        $r = new AsistenciaRepository();
        return [
            'carpeta' => 'asistencia', 'titulo' => 'Asistencia', 'singular' => 'asistencia',
            'repo' => $r, 'nuevo' => 'asistencia/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'empleado' => ['Empleado'], 'entrada' => ['Entrada'], 'salida' => ['Salida'],
                'estado' => ['Estado', 'etiqueta'],
            ],
            'campos' => [
                'empleado_id' => ['label' => 'Empleado', 'tipo' => 'select', 'req' => true,
                                  'opciones' => fn() => $r->pares('SELECT id, nombre FROM empleado ORDER BY nombre')],
                'entrada'     => ['label' => 'Entrada', 'tipo' => 'datetime-local', 'req' => true],
                'salida'      => ['label' => 'Salida (opcional)', 'tipo' => 'datetime-local', 'nulo' => true],
                'estado'      => ['label' => 'Estado', 'tipo' => 'select', 'req' => true,
                                  'opciones' => ['PRESENTE' => 'PRESENTE', 'TARDANZA' => 'TARDANZA', 'FALTA' => 'FALTA']],
            ],
        ];
    }

    protected function antes(array $d, int $id): array
    {
        if ($d['entrada'] === '') {
            throw new RuntimeException('la entrada es obligatoria.');
        }
        if ($d['salida'] !== null && $d['salida'] < $d['entrada']) {
            throw new RuntimeException('la salida no puede ser anterior a la entrada.');
        }
        return $d;
    }
}