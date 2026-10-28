<?php
class EmpleadosController extends CrudController
{
    protected function config(): array
    {
        $r = new EmpleadoRepository();
        return [
            'carpeta' => 'empleados', 'titulo' => 'Empleados', 'singular' => 'empleado',
            'repo' => $r, 'nuevo' => 'empleados/nuevo', 'acciones' => ['editar', 'eliminar'],
            'columnas' => [
                'id' => ['#'], 'nombre' => ['Nombre'], 'usuario' => ['Usuario'], 'rol' => ['Rol', 'etiqueta'],
                'sucursal' => ['Sucursal'], 'supervisor' => ['Supervisor'], 'activo' => ['Activo', 'bool'],
            ],
            'campos' => [
                'nombre'        => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 100],
                'usuario'       => ['label' => 'Usuario', 'tipo' => 'text', 'req' => true, 'max' => 30],
                'clave'         => ['label' => 'Clave', 'tipo' => 'password', 'req' => true],
                'rol'           => ['label' => 'Rol', 'tipo' => 'select', 'req' => true,
                                    'opciones' => ['ADMINISTRADOR' => 'ADMINISTRADOR', 'ALMACENERO' => 'ALMACENERO', 'CAJERO' => 'CAJERO']],
                'sucursal_id'   => ['label' => 'Sucursal', 'tipo' => 'select', 'req' => true,
                                    'opciones' => fn() => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre')],
                'supervisor_id' => ['label' => 'Supervisor', 'tipo' => 'select', 'nulo' => true,
                                    'opciones' => fn() => ['' => '— Sin supervisor —'] + $r->pares('SELECT id, nombre FROM empleado WHERE activo = 1 ORDER BY nombre')],
                'activo'        => ['label' => 'Activo', 'tipo' => 'select', 'req' => true, 'opciones' => [1 => 'Sí', 0 => 'No']],
            ],
        ];
    }

    protected function antes(array $d, int $id): array
    {
        if ($id && (int)($d['supervisor_id'] ?? 0) === $id) {
            throw new RuntimeException('un empleado no puede ser su propio supervisor.');
        }
        return $d;
    }
}