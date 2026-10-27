<?php
// models/repositories/ProductoRepository.php
class ProductoRepository {
    private $db;
    public function __construct() { $this->db = getDB(); }
    
    public function getAll() {
        $stmt = $this->db->query("SELECT p.*, c.nombre as categoria FROM producto p JOIN categoria c ON p.categoria_id = c.id");
        return $stmt->fetchAll();
    }
}