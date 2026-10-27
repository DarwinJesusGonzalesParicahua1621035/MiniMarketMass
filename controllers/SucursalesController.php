<?php
class SucursalesController extends CrudController
{
    protected function config(): array
    {
        $r = new SucursalRepository();
        return [
            'carpeta' => 'sucursales', 'titulo' => 'Sucursales', 'singular' => 'sucursal',
            'repo' => $r, 'nuevo' => 'sucursales/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'nombre' => ['Nombre'], 'direccion' => ['Dirección'], 'distrito' => ['Distrito'],
                'serie_boleta' => ['Serie boleta'], 'serie_factura' => ['Serie factura'],
            ],
            'campos' => [
                'nombre'        => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 80],
                'direccion'     => ['label' => 'Dirección', 'tipo' => 'text', 'req' => true, 'max' => 150],
                'distrito_id'   => ['label' => 'Distrito', 'tipo' => 'select', 'req' => true,
                                    'opciones' => fn() => $r->pares('SELECT id, nombre FROM distrito ORDER BY nombre')],
                'serie_boleta'  => ['label' => 'Serie boleta (ej. B001)', 'tipo' => 'text', 'req' => true, 'max' => 4],
                'serie_factura' => ['label' => 'Serie factura (ej. F001)', 'tipo' => 'text', 'req' => true, 'max' => 4],
            ],
        ];
    }
}