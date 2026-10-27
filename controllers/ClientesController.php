<?php
class ClientesController extends CrudController
{
    protected function config(): array
    {
        return [
            'carpeta' => 'clientes', 'titulo' => 'Clientes', 'singular' => 'cliente',
            'repo' => new ClienteRepository(), 'nuevo' => 'clientes/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => ['id' => ['#'], 'tipo_documento' => ['Tipo doc.'], 'numero_documento' => ['N.º documento'], 'nombre' => ['Nombre']],
            'campos' => [
                'tipo_documento'   => ['label' => 'Tipo de documento', 'tipo' => 'select', 'req' => true, 'opciones' => ['DNI' => 'DNI', 'RUC' => 'RUC']],
                'numero_documento' => ['label' => 'N.º de documento (DNI 8, RUC 11)', 'tipo' => 'text', 'req' => true, 'max' => 11,
                                       'attr' => 'pattern="[0-9]{8}|[0-9]{11}" title="8 dígitos (DNI) u 11 (RUC)"'],
                'nombre'           => ['label' => 'Nombre o razón social', 'tipo' => 'text', 'req' => true, 'max' => 120],
            ],
        ];
    }
}