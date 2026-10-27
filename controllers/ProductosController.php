<?php
class ProductosController extends CrudController
{
    protected function config(): array
    {
        $r = new ProductoRepository();
        return [
            'carpeta' => 'productos', 'titulo' => 'Productos', 'singular' => 'producto',
            'repo' => $r, 'nuevo' => 'productos/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'codigo_barras' => ['Código de barras'], 'nombre' => ['Nombre'], 'categoria' => ['Categoría'],
                'unidad' => ['Unidad'], 'precio' => ['Precio', 'dinero'], 'activo' => ['Activo', 'bool'],
            ],
            'campos' => [
                'categoria_id'  => ['label' => 'Categoría', 'tipo' => 'select', 'req' => true,
                                    'opciones' => fn() => $r->pares('SELECT id, nombre FROM categoria ORDER BY nombre')],
                'codigo_barras' => ['label' => 'Código de barras (13 dígitos)', 'tipo' => 'text', 'req' => true, 'max' => 13,
                                    'attr' => 'pattern="[0-9]{13}" title="13 dígitos"'],
                'nombre'        => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 120],
                'unidad'        => ['label' => 'Unidad', 'tipo' => 'select', 'req' => true,
                                    'opciones' => ['unidad' => 'unidad', 'kg' => 'kg', 'litro' => 'litro', 'paquete' => 'paquete']],
                'precio'        => ['label' => 'Precio (S/)', 'tipo' => 'number', 'req' => true, 'step' => '0.01', 'attr' => 'min="0"'],
                'activo'        => ['label' => 'Activo', 'tipo' => 'select', 'req' => true, 'opciones' => [1 => 'Sí', 0 => 'No']],
            ],
        ];
    }
}