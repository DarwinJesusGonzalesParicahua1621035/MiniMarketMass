<?php

class CapacitacionController
{
    private string $base = '?m=capacitaciones'; // ajustar según el router de Darwin
    private CapacitacionRepository $repo;

    public function __construct(PDO $db)
    {
        // TODO Darwin: verificación de sesión y rol.
        $this->repo = new CapacitacionRepository($db);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $capacitaciones = $this->repo->listar($q);
        $empleados = $this->repo->empleados();
        $aviso = Ayuda::tomarAviso();
        require __DIR__ . '/../views/capacitaciones/index.php';
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Ayuda::redirigir($this->base);
        }
        Ayuda::verificar();

        $id       = (int) Ayuda::post('id');
        $empleado = (int) Ayuda::post('empleado_id');
        $tema     = Ayuda::post('tema');
        $fecha    = Ayuda::post('fecha');
        $horasTxt = str_replace(',', '.', Ayuda::post('horas'));

        $error = null;
        if ($empleado <= 0) {
            $error = 'Selecciona un empleado.';
        } elseif ($tema === '' || mb_strlen($tema) > 100) {
            $error = 'El tema es obligatorio (máximo 100 caracteres).';
        } elseif (Ayuda::fecha($fecha, 'Y-m-d') === null) {
            $error = 'La fecha no es válida.';
        } elseif (!is_numeric($horasTxt) || (float) $horasTxt <= 0 || (float) $horasTxt > 9999.9) {
            $error = 'Las horas deben ser mayores que 0.';
        }

        if ($error !== null) {
            Ayuda::aviso('error', $error);
            Ayuda::redirigir($this->base);
        }

        $horas = number_format((float) $horasTxt, 1, '.', '');
        if ((float) $horas <= 0) {
            Ayuda::aviso('error', 'Las horas deben ser mayores que 0.');
            Ayuda::redirigir($this->base);
        }

        try {
            if ($id > 0) {
                $this->repo->actualizar($id, $empleado, $tema, $fecha, $horas);
                Ayuda::aviso('ok', 'Capacitación actualizada.');
            } else {
                $this->repo->crear($empleado, $tema, $fecha, $horas);
                Ayuda::aviso('ok', 'Capacitación registrada.');
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
            Ayuda::aviso('ok', 'Capacitación eliminada.');
        } catch (PDOException $e) {
            Ayuda::aviso('error', Ayuda::errorBD($e));
        }
        Ayuda::redirigir($this->base);
    }
}