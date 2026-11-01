<?php
class CajaController extends Controller
{
    protected array $roles = ['ADMINISTRADOR', 'CAJERO'];

    public function index(): void
    {
        $r = new CajaRepository();
        $sucursal = (int)($_GET['sucursal'] ?? 0) ?: null;
        $this->render('caja/index', [
            'titulo'      => 'Cajas',
            'filas'       => $r->conTurno($sucursal),
            'sucursales'  => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre'),
            'sucursalSel' => $sucursal,
            'esAdmin'     => Auth::user()['rol'] === 'ADMINISTRADOR',
        ]);
    }

    public function historial(): void
    {
        $estado = strtoupper(trim($_GET['estado'] ?? ''));
        if (!in_array($estado, ['ABIERTA', 'CERRADA'], true)) {
            $estado = null;
        }
        $this->render('caja/historial', [
            'titulo'    => 'Historial de caja',
            'filas'     => (new HistoriaCajaRepository())->historial($estado),
            'estadoSel' => $estado,
        ]);
    }

    public function nueva(): void
    {
        if (Auth::user()['rol'] !== 'ADMINISTRADOR') {
            $this->flash('Solo un administrador puede crear cajas', 'err');
            $this->redirect('caja/index');
        }
        $r = new CajaRepository();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $r->crear((int)($_POST['sucursal_id'] ?? 0), (string)($_POST['nombre'] ?? ''));
                $this->flash('Caja creada');
                $this->redirect('caja/index');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->guardarOld(['sucursal_id', 'nombre']);
                $this->flash('No se pudo crear: ' . $this->mensaje($e), 'err');
                $this->redirect('caja/nueva');
            }
        }
        $this->render('caja/nueva', [
            'titulo'     => 'Nueva caja',
            'sucursales' => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre'),
            'old'        => $this->tomarOld(),
        ]);
    }

    public function abrir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new HistoriaCajaRepository())->abrir(
                    (int)($_POST['caja_id'] ?? 0),
                    (int)Auth::user()['id'],
                    (string)($_POST['monto_inicial'] ?? '')
                );
                $this->flash('Caja abierta');
                $this->volver('caja/index');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->guardarOld(['caja_id', 'monto_inicial']);
                $this->flash('No se pudo abrir: ' . $this->mensaje($e), 'err');
                $this->redirect('caja/abrir');
            }
        }
        $this->render('caja/abrir', [
            'titulo'  => 'Abrir caja',
            'cajas'   => (new CajaRepository())->libres(),
            'cajaSel' => (int)($_GET['id'] ?? 0),
            'old'     => $this->tomarOld(),
        ]);
    }

    public function cerrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new HistoriaCajaRepository())->cerrar(
                    (int)($_POST['historia_id'] ?? 0),
                    (string)($_POST['monto_final'] ?? '')
                );
                $this->flash('Caja cerrada');
                $this->volver('caja/historial');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->guardarOld(['historia_id', 'monto_final']);
                $this->flash('No se pudo cerrar: ' . $this->mensaje($e), 'err');
                $this->volver('caja/cerrar');
            }
        }
        $this->render('caja/cerrar', [
            'titulo'    => 'Cerrar caja',
            'turnos'    => (new HistoriaCajaRepository())->abiertas(),
            'turnoSel'  => (int)($_GET['id'] ?? 0),
            'old'       => $this->tomarOld(),
        ]);
    }

    // Datos escritos antes de un error (se muestran una sola vez).
    // Dónde volver: el cajero a su pantalla; el administrador al panel.
    private function volver(string $admin): void
    {
        $this->redirect(Auth::user()['rol'] === 'CAJERO' ? 'cajero/caja' : $admin);
    }

    // Datos escritos antes de un error (se muestran una sola vez).
    private function guardarOld(array $campos): void
    {
        $_SESSION['caja_old'] = [];
        foreach ($campos as $c) {
            $_SESSION['caja_old'][$c] = (string)($_POST[$c] ?? '');
        }
    }

    private function tomarOld(): array
    {
        $old = $_SESSION['caja_old'] ?? [];
        unset($_SESSION['caja_old']);
        return $old;
    }
}
