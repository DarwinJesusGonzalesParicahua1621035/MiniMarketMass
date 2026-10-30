<?php
class ProductosController extends CrudController
{
    protected function config(): array
    {
        $r = new ProductoRepository();
        return [
            'carpeta' => 'productos', 'titulo' => 'Productos', 'singular' => 'producto',
            'repo' => $r,
            'campos' => [
                'categoria_id'  => ['label' => 'Categoría', 'tipo' => 'select', 'req' => true,
                                    'opciones' => fn() => ['' => '— Elige una categoría —']
                                        + $r->pares('SELECT id, nombre FROM categoria ORDER BY nombre')],
                'codigo_barras' => ['label' => 'Código de barras (13 caracteres)', 'tipo' => 'text', 'req' => true,
                                    'max' => 13, 'attr' => 'minlength="13" pattern=".{13}"'],
                'nombre'        => ['label' => 'Nombre', 'tipo' => 'text', 'req' => true, 'max' => 120],
                'unidad'        => ['label' => 'Unidad', 'tipo' => 'text', 'req' => true, 'max' => 20,
                                    'attr' => 'placeholder="Ej: unidad, kg, litro"'],
                'precio'        => ['label' => 'Precio (S/)', 'tipo' => 'number', 'req' => true,
                                    'step' => '0.01', 'attr' => 'min="0" max="99999999.99"'],
                'activo'        => ['label' => 'Activo', 'tipo' => 'select', 'req' => true, 'opciones' => [1 => 'Sí', 0 => 'No']],
            ],
        ];
    }

    public function index(): void
    {
        $r = new ProductoRepository();
        $categoria = (int)($_GET['categoria'] ?? 0) ?: null;
        $this->render('productos/index', [
            'titulo'       => 'Productos',
            'filas'        => $r->resumen($categoria),
            'categorias'   => $r->pares('SELECT id, nombre FROM categoria ORDER BY nombre'),
            'categoriaSel' => $categoria,
        ]);
    }

    // Detalle del producto con su historial de precios.
    public function ver(): void
    {
        $r = new ProductoRepository();
        $p = $r->detalle((int)($_GET['id'] ?? 0));
        if (!$p) {
            $this->redirect('productos/index');
        }
        $this->render('productos/ver', [
            'titulo'    => 'Producto',
            'p'         => $p,
            'historial' => $r->historial((int)$p['id']),
        ]);
    }

    // Reglas del schema que MySQL no valida solo (CHAR(13) rellena en vez de rechazar).
    protected function antes(array $d, int $id): array
    {
        $r = new ProductoRepository();

        if (mb_strlen($d['codigo_barras']) !== 13) {
            throw new RuntimeException('el código de barras debe tener exactamente 13 caracteres.');
        }
        if ($r->existeCodigo($d['codigo_barras'], $id)) {
            throw new RuntimeException('ya existe un producto con ese código de barras.');
        }
        if ($d['nombre'] === '' || $d['unidad'] === '') {
            throw new RuntimeException('el nombre y la unidad son obligatorios.');
        }
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $d['precio'])) {
            throw new RuntimeException('el precio debe ser un número mayor o igual a 0, con hasta 2 decimales.');
        }
        if (!in_array($d['activo'], ['0', '1'], true)) {
            throw new RuntimeException('el estado no es válido.');
        }
        return $d;
    }

    // Los productos no se borran (tienen historial y ventas): se desactivan con Activo = No.
    public function eliminar(): void
    {
        $this->flash('Los productos no se eliminan. Para retirarlo, edítalo y ponlo como inactivo.', 'err');
        $this->redirect('productos/index');
    }
}