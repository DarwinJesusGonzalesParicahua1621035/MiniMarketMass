<?php

class SucursalController
{
    private string $base = '?m=sucursales'; // ajustar según el router de Darwin
    private SucursalRepository $repo;

    public function __construct(PDO $db)
    {
        // TODO Darwin: aquí va la verificación de sesión y rol (ADMINISTRADOR).
        $this->repo = new SucursalRepository($db);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $sucursales = $this->repo->listar($q);
        $distritos = $this->repo->distritos();
        $aviso = Ayuda::tomarAviso();
        require __DIR__ . '/../views/sucursales/index.php';
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Ayuda::redirigir($this->base);
        }
        Ayuda::verificar();

        $id        = (int) Ayuda::post('id');
        $nombre    = Ayuda::post('nombre');
        $direccion = Ayuda::post('direccion');
        $distrito  = (int) Ayuda::post('distrito_id');
        $serieB    = strtoupper(Ayuda::post('serie_boleta'));
        $serieF    = strtoupper(Ayuda::post('serie_factura'));

        $error = null;
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            $error = 'El nombre es obligatorio (máximo 80 caracteres).';
        } elseif ($direccion === '' || mb_strlen($direccion) > 150) {
            $error = 'La dirección es obligatoria (máximo 150 caracteres).';
        } elseif ($distrito <= 0) {
            $error = 'Selecciona un distrito.';
        } elseif (!preg_match('/^[A-Z0-9]{4}$/', $serieB)) {
            $error = 'La serie de boleta debe tener exactamente 4 caracteres (letras o números).';
        } elseif (!preg_match('/^[A-Z0-9]{4}$/', $serieF)) {
            $error = 'La serie de factura debe tener exactamente 4 caracteres (letras o números).';
        }

        if ($error !== null) {
            Ayuda::aviso('error', $error);
            Ayuda::redirigir($this->base);
        }

        try {
            if ($id > 0) {
                $this->repo->actualizar($id, $nombre, $direccion, $distrito, $serieB, $serieF);
                Ayuda::aviso('ok', 'Sucursal actualizada.');
            } else {
                $this->repo->crear($nombre, $direccion, $distrito, $serieB, $serieF);
                Ayuda::aviso('ok', 'Sucursal creada.');
            }
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                error_log($e->getMessage());
                Ayuda::aviso('error', 'La serie de boleta o de factura ya está en uso por otra sucursal.');
            } else {
                Ayuda::aviso('error', Ayuda::errorBD($e));
            }
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
            Ayuda::aviso('ok', 'Sucursal eliminada.');
        } catch (PDOException $e) {
            Ayuda::aviso('error', Ayuda::errorBD($e));
        }
        Ayuda::redirigir($this->base);
    }
}