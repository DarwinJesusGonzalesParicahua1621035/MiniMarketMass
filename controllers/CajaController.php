<?php
class CajaController extends CrudController
{
    protected function config(): array
    {
        $r = new CajaRepository();
        return [
            'carpeta' => 'caja', 'titulo' => 'Cajas', 'singular' => 'caja',
            'repo' => $r, 'nuevo' => 'caja/nuevo', 'acciones' => ['editar', 'eliminar'],
            'extra' => [['Historial de aperturas', 'caja/historial']],
            'columnas' => [
                'id' => ['#'], 'sucursal' => ['Sucursal'], 'caja' => ['Caja'], 'cajero' => ['Cajero'],
                'apertura' => ['Apertura'], 'monto_inicial' => ['Monto inicial', 'dinero'], 'estado' => ['Estado', 'etiqueta'],
            ],
            'campos' => [
                'sucursal_id' => ['label' => 'Sucursal', 'tipo' => 'select', 'req' => true,
                                  'opciones' => fn() => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre')],
                'nombre'      => ['label' => 'Nombre de la caja', 'tipo' => 'text', 'req' => true, 'max' => 40],
            ],
        ];
    }

    public function historial(): void
    {
        $c = [
            'carpeta' => 'caja', 'titulo' => 'Historial de cajas', 'extra' => [['← Cajas', 'caja/index']],
            'columnas' => [
                'id' => ['#'], 'sucursal' => ['Sucursal'], 'caja' => ['Caja'], 'cajero' => ['Cajero'], 'apertura' => ['Apertura'],
                'cierre' => ['Cierre'], 'monto_inicial' => ['Monto inicial', 'dinero'], 'monto_final' => ['Monto final', 'dinero'],
                'estado' => ['Estado', 'etiqueta'],
            ],
        ];
        $this->render('caja/historial', ['c' => $c, 'filas' => (new CajaRepository())->historial(), 'titulo' => $c['titulo']]);
    }
}