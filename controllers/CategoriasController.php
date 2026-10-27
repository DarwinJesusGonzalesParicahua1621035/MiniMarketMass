<?php
class CategoriasController extends CrudController
{
    protected function config(): array
    {
        return [
            'carpeta' => 'categorias', 'titulo' => 'Categorías', 'singular' => 'categoría',
            'repo' => new CategoriaRepository(), 'nuevo' => 'categorias/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => ['id' => ['#'], 'nombre' => ['Nombre']],
            'campos' => ['nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 60]],
        ];
    }
}