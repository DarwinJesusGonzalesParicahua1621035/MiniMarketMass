<?php
class ClientesController extends CrudController
{
    protected function config(): array
    {
        return [
            'carpeta' => 'clientes', 'titulo' => 'Clientes', 'singular' => 'cliente',
            'repo' => new ClienteRepository(), 'nuevo' => 'clientes/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'tipo_documento' => ['Tipo doc.', 'etiqueta'], 'numero_documento' => ['N.º documento'],
                'nombre' => ['Nombre'], 'ventas' => ['Compras'],
            ],
            'campos' => [
                'tipo_documento'   => ['label' => 'Tipo de documento', 'tipo' => 'select', 'req' => true,
                                       'opciones' => ['DNI' => 'DNI', 'RUC' => 'RUC']],
                'numero_documento' => ['label' => 'N.º de documento (DNI 8 dígitos, RUC 11)', 'tipo' => 'text', 'req' => true, 'max' => 11],
                'nombre'           => ['label' => 'Nombre o razón social', 'tipo' => 'text', 'req' => true, 'max' => 120],
            ],
        ];
    }

    protected function antes(array $d, int $id): array
    {
        $largo = $d['tipo_documento'] === 'RUC' ? 11 : 8;
        if (!preg_match('/^\d{' . $largo . '}$/', $d['numero_documento'])) {
            throw new RuntimeException("el {$d['tipo_documento']} debe tener $largo dígitos.");
        }
        if ($d['nombre'] === '') {
            throw new RuntimeException('el nombre es obligatorio.');
        }
        return $d;
    }
}