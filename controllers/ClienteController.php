<?php

// Si usas namespaces, descomenta y ajusta las siguientes líneas:
// use App\Repositories\ClienteRepository;
// use App\Helpers\Ayuda;

class ClienteController
{
    private string $base = '?m=clientes'; // ajustar según el router de Darwin
    private ClienteRepository $repo;

    public function __construct(PDO $db)
    {
        // TODO Darwin: verificación de sesión y rol.
        $this->repo = new ClienteRepository($db);
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $clientes = $this->repo->listar($q);
        $aviso = Ayuda::tomarAviso();
        require __DIR__ . '/../views/clientes/index.php';
    }

    public function guardar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Ayuda::redirigir($this->base);
        }
        Ayuda::verificar();

        $id     = (int) Ayuda::post('id');
        $tipo   = Ayuda::post('tipo_documento');
        $numero = Ayuda::post('numero_documento');
        $nombre = Ayuda::post('nombre');

        $error = null;
        if (!in_array($tipo, ['DNI', 'RUC'], true)) {
            $error = 'El tipo de documento debe ser DNI o RUC.';
        } elseif (!ctype_digit($numero)) {
            $error = 'El número de documento solo admite dígitos.';
        } elseif ($tipo === 'DNI' && strlen($numero) !== 8) {
            $error = 'El DNI debe tener 8 dígitos.';
        } elseif ($tipo === 'RUC' && strlen($numero) !== 11) {
            $error = 'El RUC debe tener 11 dígitos.';
        } elseif ($nombre === '' || mb_strlen($nombre) > 120) {
            $error = 'El nombre es obligatorio (máximo 120 caracteres).';
        }

        if ($error !== null) {
            Ayuda::aviso('error', $error);
            Ayuda::redirigir($this->base);
        }

        try {
            if ($id > 0) {
                $this->repo->actualizar($id, $tipo, $numero, $nombre);
                Ayuda::aviso('ok', 'Cliente actualizado.');
            } else {
                $this->repo->crear($tipo, $numero, $nombre);
                Ayuda::aviso('ok', 'Cliente creado.');
            }
        } catch (PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                error_log($e->getMessage());
                Ayuda::aviso('error', 'Ya existe un cliente con ese tipo y número de documento.');
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
            Ayuda::aviso('ok', 'Cliente eliminado.');
        } catch (PDOException $e) {
            Ayuda::aviso('error', Ayuda::errorBD($e)); // si tiene ventas: "está siendo usado..."
        }
        Ayuda::redirigir($this->base);
    }
}