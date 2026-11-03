<?php
class CategoriasController extends CrudController
{
    protected function config(): array
    {
        return [
            'carpeta' => 'categorias', 'titulo' => 'Categorías', 'singular' => 'categoría',
            'repo' => new CategoriaRepository(), 'nuevo' => 'categorias/nuevo',
            'extra' => [['Ver productos', 'productos/index']],
            'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'nombre' => ['Nombre'], 'productos' => ['Productos'],
            ],
            'campos' => [
                'nombre' => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 60],
            ],
        ];
    }

    protected function antes(array $d, int $id): array
    {
        if ($d['nombre'] === '') {
            throw new RuntimeException('el nombre es obligatorio.');
        }
        return $d;
    }
}