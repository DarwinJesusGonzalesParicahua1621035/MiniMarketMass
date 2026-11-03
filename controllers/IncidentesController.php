<?php
class IncidentesController extends CrudController
{
    protected function config(): array
    {
        $r = new IncidenteRepository();
        return [
            'carpeta' => 'incidentes', 'titulo' => 'Incidentes', 'singular' => 'incidente',
            'repo' => $r, 'nuevo' => 'incidentes/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'sucursal' => ['Sucursal'], 'tipo' => ['Tipo'], 'descripcion' => ['Descripción'],
                'fecha' => ['Fecha'], 'estado' => ['Estado', 'etiqueta'],
            ],
            'campos' => [
                'sucursal_id' => ['label' => 'Sucursal', 'tipo' => 'select', 'req' => true,
                                  'opciones' => fn() => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre')],
                'tipo'        => ['label' => 'Tipo', 'tipo' => 'text', 'req' => true, 'max' => 20,
                                  'attr' => 'placeholder="Ej: Seguridad, Equipos, Stock"'],
                'descripcion' => ['label' => 'Descripción', 'tipo' => 'text', 'req' => true, 'max' => 250],
                'fecha'       => ['label' => 'Fecha (vacío = ahora)', 'tipo' => 'datetime-local'],
                'estado'      => ['label' => 'Estado', 'tipo' => 'select', 'req' => true,
                                  'opciones' => ['ABIERTO' => 'ABIERTO', 'RESUELTO' => 'RESUELTO']],
            ],
        ];
    }

    protected function antes(array $d, int $id): array
    {
        if ($d['tipo'] === '' || $d['descripcion'] === '') {
            throw new RuntimeException('tipo y descripción son obligatorios.');
        }
        if (!in_array($d['estado'], ['ABIERTO', 'RESUELTO'], true)) {
            throw new RuntimeException('el estado debe ser ABIERTO o RESUELTO.');
        }
        if ($d['fecha'] === '') {
            $d['fecha'] = date('Y-m-d H:i:s');
        }
        return $d;
    }
}
