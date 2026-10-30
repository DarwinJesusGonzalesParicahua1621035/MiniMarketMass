<?php
class CajeroController extends Controller
{
    protected array $roles = ['CAJERO', 'ADMINISTRADOR'];

    public function index(): void
    {
        $s = Database::pdo()->prepare(
            'SELECT s.nombre FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id WHERE e.id = ?'
        );
        $s->execute([Auth::user()['id']]);
        $sucursal = $s->fetchColumn() ?: '';

        $this->render('cajero/index', ['titulo' => 'Caja', 'sucursal' => $sucursal], false);
    }
}