<?php

class AsistenciaController
{
    private string $base = '?m=asistencia'; // ajustar según el router de Darwin
    private AsistenciaRepository $repo;

    public function __construct(PDO $db)
    {
        // TODO Darwin: verificación de sesión y rol.
        $this->repo = new AsistenciaRepository($db);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $asistencias = $this->repo->listar($q);
        $empleados = $this->repo->empleados();
        $aviso = Ayuda::tomarAviso();
        require __DIR__ . '/../views/asistencia/index.php';
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Ayuda::redirigir($this->base);
        }
        Ayuda::verificar();

        $id        = (int) Ayuda::post('id');
        $empleado  = (int) Ayuda::post('empleado_id');
        $entradaTx = Ayuda::post('entrada');
        $salidaTx  = Ayuda::post('salida');
        $estado    = Ayuda::post('estado') ?: 'PRESENTE';

        $formato = 'Y-m-d\TH:i'; // formato de <input type="datetime-local">
        $entrada = Ayuda::fecha($entradaTx, $formato);
        $salida  = $salidaTx === '' ? null : Ayuda::fecha($salidaTx, $formato);

        $error = null;
        if ($empleado <= 0) {
            $error = 'Selecciona un empleado.';
        } elseif ($entrada === null) {
            $error = 'La entrada no es válida.';
        } elseif ($salidaTx !== '' && $salida === null) {
            $error = 'La salida no es válida.';
        } elseif ($salida !== null && $salida < $entrada) {
            $error = 'La salida no puede ser anterior a la entrada.';
        } elseif (!in_array($estado, ['FALTA', 'PRESENTE', 'TARDANZA'], true)) {
            $error = 'El estado debe ser FALTA, PRESENTE o TARDANZA.';
        }

        if ($error !== null) {
            Ayuda::aviso('error', $error);
            Ayuda::redirigir($this->base);
        }

        $entradaSql = $entrada->format('Y-m-d H:i:s');
        $salidaSql  = $salida?->format('Y-m-d H:i:s');

        try {
            if ($id > 0) {
                $this->repo->actualizar($id, $empleado, $entradaSql, $salidaSql, $estado);
                Ayuda::aviso('ok', 'Asistencia actualizada.');
            } else {
                $this->repo->crear($empleado, $entradaSql, $salidaSql, $estado);
                Ayuda::aviso('ok', 'Asistencia registrada.');
            }
        } catch (PDOException $e) {
            Ayuda::aviso('error', Ayuda::errorBD($e));
        }
        Ayuda::redirigir($this->base);
    }

    public function eliminar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Ayuda::redirigir($this->base);
        }
        Ayuda::verificar();
        $id = (int) Ayuda::post('id');

        if ($id <= 0) {
            Ayuda::aviso('error', 'Registro no válido.');
            Ayuda::redirigir($this->base);
        }
        try {
            $this->repo->eliminar($id);
            Ayuda::aviso('ok', 'Asistencia eliminada.');
        } catch (PDOException $e) {
            Ayuda::aviso('error', Ayuda::errorBD($e));
        }
        Ayuda::redirigir($this->base);
    }
}