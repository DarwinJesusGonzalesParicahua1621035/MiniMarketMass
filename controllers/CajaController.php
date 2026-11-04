<?php
class CajaController extends Controller
{
    protected array $roles = ['ADMINISTRADOR', 'CAJERO'];

    private function filtros(): array
    {
        $hoy = date('Y-m-d');
        $fecha = function (string $k): string {
            $v = $_GET[$k] ?? '';
            $d = DateTime::createFromFormat('Y-m-d', (string)$v);
            return ($d && $d->format('Y-m-d') === $v) ? $v : '';
        };
        $primera = !isset($_GET['f']) && !isset($_GET['desde']) && !isset($_GET['hasta']);
        $desde = $primera ? date('Y-m-d', strtotime("$hoy -6 days")) : $fecha('desde');
        $hasta = $primera ? $hoy : $fecha('hasta');
        if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
            [$desde, $hasta] = [$hasta, $desde];
        }
        $ps = (int)($_GET['ps'] ?? 25);
        return [
            'desde'    => $desde,
            'hasta'    => $hasta,
            'sucursal' => (int)($_GET['sucursal'] ?? 0),
            'resp'     => (int)($_GET['resp'] ?? 0),
            'estado'   => in_array($_GET['estado'] ?? '', ['ABIERTA', 'CERRADA'], true) ? $_GET['estado'] : '',
            'dif'      => in_array($_GET['dif'] ?? '', ['cuadra', 'falta', 'sobra'], true) ? $_GET['dif'] : '',
            'q'        => mb_substr(trim($_GET['q'] ?? ''), 0, 60),
            'orden'    => in_array($_GET['orden'] ?? '', ['apertura', 'tot', 'dif'], true) ? $_GET['orden'] : 'apertura',
            'dir'      => ($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc',
            'ps'       => in_array($ps, [25, 50, 100], true) ? $ps : 25,
            'pag'      => max(1, (int)($_GET['pag'] ?? 1)),
        ];
    }

    public function index(): void
    {
        $c = new CajaRepository();
        $h = new HistoriaCajaRepository();
        $f = $this->filtros();
        $rep = $h->reporte($f);
        $paginas = max(1, (int)ceil((int)$rep['n'] / $f['ps']));
        $f['pag'] = min($f['pag'], $paginas);
        $u = Auth::user();
        $this->render('caja/index', [
            'titulo'       => 'Cajas',
            'f'            => $f,
            'rep'          => $rep,
            'paginas'      => $paginas,
            'filas'        => $h->listado($f, $f['ps'], ($f['pag'] - 1) * $f['ps']),
            'cajas'        => $c->ahora(),
            'sucursales'   => $c->sucursales(),
            'responsables' => $c->responsables(),
            'esAdmin'      => $u['rol'] === 'ADMINISTRADOR',
            'yoId'         => (int)$u['id'],
        ]);
    }

    public function ventas(): void
    {
        $t = (new HistoriaCajaRepository())->ventasDe((int)($_GET['id'] ?? 0));
        header('Content-Type: application/json; charset=utf-8');
        if (!$t) {
            http_response_code(404);
            exit('{}');
        }
        exit(json_encode($t, JSON_UNESCAPED_UNICODE));
    }

    public function exportar(): void
    {
        $f = $this->filtros();
        $filas = (new HistoriaCajaRepository())->listado($f, 5000, 0);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historial-cajas.csv"');
        $o = fopen('php://output', 'w');
        fwrite($o, "\xEF\xBB\xBF");
        fputcsv($o, ['N°', 'Sucursal', 'Caja', 'Responsable', 'Apertura', 'Cierre', 'Monto inicial', 'Ventas', 'Diferencia', 'Estado']);
        foreach ($filas as $t) {
            fputcsv($o, [
                $t['id'], $t['sucursal'], $t['caja'], $t['resp'], $t['apertura'], $t['cierre'] ?? '',
                $t['ini'], $t['tot'], $t['dif'] ?? '', $t['estado'] === 'CERRADA' ? 'Cerrado' : 'Abierto',
            ]);
        }
        fclose($o);
        exit;
    }

    public function historial(): void
    {
        $this->redirect('caja/index');
    }

    public function nueva(): void
    {
        if (Auth::user()['rol'] !== 'ADMINISTRADOR') {
            $this->flash('Solo un administrador puede crear cajas', 'err');
            $this->redirect('caja/index');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new CajaRepository())->crear((int)($_POST['sucursal_id'] ?? 0), (string)($_POST['nombre'] ?? ''));
                $this->flash('Caja creada');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo crear: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('caja/index');
    }

    public function abrir(): void
    {
        $u = Auth::user();
        if ($u['rol'] === 'ADMINISTRADOR') {
            $this->flash('El administrador no abre cajas: solo puede crearlas, cerrarlas y ver sus ventas', 'err');
            $this->redirect('caja/index');
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $id = (int)($_GET['id'] ?? 0);
            $this->redirect('caja/index', $id ? ['abrir' => $id] : []);
        }
        try {
            (new HistoriaCajaRepository())->abrir((int)($_POST['caja_id'] ?? 0), (int)$u['id'], (string)($_POST['monto_inicial'] ?? ''));
            $this->flash('Caja abierta');
        } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
            $this->flash('No se pudo abrir: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('caja/index');
    }

    public function cerrar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                (new HistoriaCajaRepository())->cerrar((int)($_POST['historia_id'] ?? 0), (string)($_POST['contado'] ?? ''));
                $this->flash('Caja cerrada');
            } catch (InvalidArgumentException | RuntimeException | PDOException $e) {
                $this->flash('No se pudo cerrar: ' . $this->mensaje($e), 'err');
            }
        }
        $this->redirect('caja/index');
    }
}