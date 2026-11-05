<?php
class SucursalesController extends CrudController
{
    protected function config(): array
{
    $r = new SucursalRepository();
    return [
        'carpeta' => 'sucursales', 'titulo' => 'Sucursales', 'singular' => 'sucursal',
        'repo' => $r,
        'campos' => [
            'nombre'        => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 80],
            'direccion'     => ['label' => 'Dirección', 'tipo' => 'text', 'req' => true, 'max' => 150],
            'distrito_id'   => ['label' => 'Distrito', 'tipo' => 'select', 'req' => true,
                                'opciones' => fn() => $r->pares('SELECT id, nombre FROM distrito ORDER BY nombre')],
            'serie_boleta'  => ['label' => 'Serie de boleta (4 caracteres, ej. B001)', 'tipo' => 'text', 'req' => true, 'max' => 4],
            'serie_factura' => ['label' => 'Serie de factura (4 caracteres, ej. F001)', 'tipo' => 'text', 'req' => true, 'max' => 4],
        ],
    ];
}

    protected function antes(array $d, int $id): array
    {
        foreach (['serie_boleta', 'serie_factura'] as $k) {
            $d[$k] = strtoupper($d[$k]);
            if (strlen($d[$k]) !== 4) {
                throw new RuntimeException('las series deben tener exactamente 4 caracteres.');
            }
        }
        if ($d['serie_boleta'] === $d['serie_factura']) {
            throw new RuntimeException('la serie de boleta y la de factura deben ser distintas.');
        }
        return $d;
    }
}