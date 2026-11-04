<?php
class ProveedoresController extends CrudController
{
    protected function config(): array
    {
        return [
            'carpeta' => 'proveedores', 'titulo' => 'Proveedores', 'singular' => 'proveedor',
            'repo' => new ProveedorRepository(),
            'campos' => [
                'ruc'          => ['label' => 'RUC (11 dígitos)', 'tipo' => 'text', 'req' => true, 'max' => 11,
                                   'attr' => 'inputmode="numeric" pattern="\d{11}"'],
                'razon_social' => ['label' => 'Razón social', 'tipo' => 'text', 'req' => true, 'max' => 120],
                'contacto'     => ['label' => 'Persona de contacto', 'tipo' => 'text', 'req' => true, 'max' => 100],
                'telefono'     => ['label' => 'Teléfono', 'tipo' => 'text', 'req' => true, 'max' => 15],
                'correo'       => ['label' => 'Correo (opcional)', 'tipo' => 'email', 'max' => 100, 'nulo' => true],
                'direccion'    => ['label' => 'Dirección (opcional)', 'tipo' => 'text', 'max' => 150, 'nulo' => true],
                'activo'       => ['label' => 'Activo', 'tipo' => 'select', 'req' => true, 'opciones' => [1 => 'Sí', 0 => 'No']],
            ],
        ];
    }

    public function index(): void
    {
        $r = new ProveedorRepository();
        $this->render('proveedores/index', [
            'titulo' => 'Proveedores',
            'filas'  => $r->todos(),
            'prods'  => $r->productosPorProveedor(),
        ]);
    }

    // Reglas del schema que MySQL no valida solo (CHAR(11) rellena en vez de rechazar).
    protected function antes(array $d, int $id): array
    {
        $r = new ProveedorRepository();

        if (!preg_match('/^\d{11}$/', $d['ruc'])) {
            throw new RuntimeException('el RUC debe tener exactamente 11 dígitos.');
        }
        if ($r->existeRuc($d['ruc'], $id)) {
            throw new RuntimeException('ya existe un proveedor con ese RUC.');
        }
        if ($d['razon_social'] === '' || $d['contacto'] === '') {
            throw new RuntimeException('la razón social y el contacto son obligatorios.');
        }
        if (!preg_match('/^[0-9+\-\s()]{6,15}$/', $d['telefono'])) {
            throw new RuntimeException('el teléfono no es válido (solo números, de 6 a 15 caracteres).');
        }
        if ($d['correo'] !== null && !filter_var($d['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('el correo no tiene un formato válido.');
        }
        if (!in_array($d['activo'], ['0', '1'], true)) {
            throw new RuntimeException('el estado no es válido.');
        }
        return $d;
    }

    // Un proveedor con productos no se borra: se desactiva con Activo = No.
    public function eliminar(): void
    {
        $r  = new ProveedorRepository();
        $id = (int)($_POST['id'] ?? 0);
        $n  = $r->contarProductos($id);

        if ($n > 0) {
            $this->flash("No se puede eliminar: este proveedor abastece $n producto(s). Edítalo y ponlo como inactivo.", 'err');
        } else {
            try {
                $r->eliminar($id);
                $this->flash('Registro eliminado');
            } catch (PDOException $e) {
                $this->flash('No se pudo eliminar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('proveedores/index');
    }
}