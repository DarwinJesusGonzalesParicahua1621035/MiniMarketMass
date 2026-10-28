<?php
abstract class CrudController extends Controller
{
    abstract protected function config(): array;

    protected function antes(array $d, int $id): array { return $d; }

    public function index(): void
    {
        $c = $this->config();
        $this->render($c['carpeta'] . '/index', ['c' => $c, 'filas' => $c['repo']->todos(), 'titulo' => $c['titulo']]);
    }

    public function nuevo(): void { $this->formulario(0); }

    public function editar(): void { $this->formulario((int)($_GET['id'] ?? 0)); }

    private function formulario(int $id): void
    {
        $c = $this->config();
        $reg = $id ? $c['repo']->buscar($id) : [];
        if ($id && !$reg) {
            $this->redirect($c['carpeta'] . '/index');
        }
        foreach ($c['campos'] as &$f) {
            if (isset($f['opciones']) && is_callable($f['opciones'])) {
                $f['opciones'] = ($f['opciones'])();
            }
        }
        unset($f);
        $this->render($c['carpeta'] . '/form', [
            'c' => $c, 'reg' => $reg, 'id' => $id,
            'titulo' => ($id ? 'Editar ' : 'Nuevo ') . $c['singular'],
        ]);
    }

    public function guardar(): void
    {
        $c = $this->config();
        $id = (int)($_POST['id'] ?? 0);
        $d = [];
        foreach ($c['campos'] as $n => $f) {
            $v = trim($_POST[$n] ?? '');
            if ($f['tipo'] === 'password') {
                if ($v === '') continue;
                $v = password_hash($v, PASSWORD_DEFAULT);
            } elseif ($f['tipo'] === 'datetime-local' && $v !== '') {
                $v = str_replace('T', ' ', $v);
                if (strlen($v) === 16) $v .= ':00';
            }
            if ($v === '' && !empty($f['nulo'])) $v = null;
            $d[$n] = $v;
        }
        try {
            $d = $this->antes($d, $id);
            $id ? $c['repo']->actualizar($id, $d) : $c['repo']->insertar($d);
            $this->flash('Guardado correctamente');
        } catch (PDOException | RuntimeException $e) {
            $this->flash('No se pudo guardar: ' . $this->mensaje($e), 'err');
        }
        $this->redirect($c['carpeta'] . '/index');
    }

    public function eliminar(): void
    {
        $c = $this->config();
        try {
            $c['repo']->eliminar((int)($_POST['id'] ?? 0));
            $this->flash('Registro eliminado');
        } catch (PDOException $e) {
            $this->flash('No se pudo eliminar: ' . $this->mensaje($e), 'err');
        }
        $this->redirect($c['carpeta'] . '/index');
    }
}