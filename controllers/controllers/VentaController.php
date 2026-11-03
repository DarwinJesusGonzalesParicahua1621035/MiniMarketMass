<?php
require_once __DIR__ . '/../models/venta.php';

class VentaController {
    private $model;

    public function __construct($pdo) {
        $this->model = new VentaModel($pdo);
    }

    public function index() {
        // Datos simulados de sesión (Darwin debería tener esto en su Auth)
        $id_sucursal = 1; // Mass Cayma
        $id_empleado = 2; // Jorge Salas

        $productos = $this->model->obtenerProductos($id_sucursal);
        
        // Cargamos la vista POS pasando los datos reales
        require_once __DIR__ . '/../views/ventas/pos.php';
    }

    public function guardar() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Aquí deberías obtener el ID del empleado logueado
            $input['id_empleado'] = 2; 
            
            try {
                $idVenta = $this->model->registrarVenta($input);
                echo json_encode(['success' => true, 'id_venta' => $idVenta]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
}
?>