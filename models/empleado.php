<?php
// models/empleado.php
class Empleado {
    public function findByUsername($username) {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM empleado WHERE usuario = ?");
        $stmt->execute([$username]);
        return $stmt->fetch();
    }
}