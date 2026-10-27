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
                'tipo'        => ['label' => 'Tipo', 'tipo' => 'select', 'req' => true,
                                  'opciones' => ['Seguridad' => 'Seguridad', 'Stock' => 'Stock', 'Equipos' => 'Equipos', 'Limpieza' => 'Limpieza', 'Otro' => 'Otro']],
                'descripcion' => ['label' => 'Descripción', 'tipo' => 'text', 'req' => true, 'max' => 250],
                'fecha'       => ['label' => 'Fecha y hora', 'tipo' => 'datetime-local', 'req' => true],
                'estado'      => ['label' => 'Estado', 'tipo' => 'select', 'req' => true, 'opciones' => ['ABIERTO' => 'ABIERTO', 'RESUELTO' => 'RESUELTO']],
            ],
        ];
    }
}