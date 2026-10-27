<?php
// controllers/ProductoController.php
require_once __DIR__ . '/../models/repositories/ProductoRepository.php';

class ProductoController {
    public function index() {
        $repo = new ProductoRepository();
        $productos = $repo->getAll();
        
        // Pasa los datos a la vista
        require_once __DIR__ . '/../views/layout/header.php';
        require_once __DIR__ . '/../views/productos/index.php';
        require_once __DIR__ . '/../views/layout/footer.php';
    }
}