<?php
require_once __DIR__ . '/../config/database.php';

class VentaModel {
    private $db;

    public function __construct($pdo) {
        $this->db = $pdo;
    }

    // Obtener productos activos con su stock para la sucursal
    public function obtenerProductos($id_sucursal) {
        $sql = "SELECT p.id_producto, p.codigo_barras, p.nombre, c.nombre AS categoria, 
                    p.unidad_medida, p.precio_venta, s.cantidad AS stock
                FROM producto p
                LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
                LEFT JOIN stock s ON p.id_producto = s.id_producto AND s.id_sucursal = ?
                WHERE p.activo = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_sucursal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Registrar la venta completa (Transacción)
    public function registrarVenta($datos) {
        try {
            $this->db->beginTransaction();

            // 1. Insertar Cabecera de Venta
            $sqlVenta = "INSERT INTO venta (id_sucursal, id_cliente, id_empleado, tipo_comprobante, serie, numero, fecha_venta, subtotal, igv, total, estado) 
                        VALUES (:sucursal, :cliente, :empleado, :tipo, :serie, :numero, NOW(), :subtotal, :igv, :total, 'EMITIDA')";
            $stmt = $this->db->prepare($sqlVenta);
            $stmt->execute([
                ':sucursal' => $datos['id_sucursal'],
                ':cliente'  => $datos['id_cliente'] ?: null,
                ':empleado' => $datos['id_empleado'],
                ':tipo'     => $datos['tipo_comprobante'],
                ':serie'    => $datos['serie'],
                ':numero'   => $datos['numero'],
                ':subtotal' => $datos['subtotal'],
                ':igv'      => $datos['igv'],
                ':total'    => $datos['total']
            ]);
            $idVenta = $this->db->lastInsertId();

            // 2. Insertar Detalle y descontar Stock
            $sqlDetalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);

            $sqlStock = "UPDATE stock SET cantidad = cantidad - ? WHERE id_sucursal = ? AND id_producto = ?";
            $stmtStock = $this->db->prepare($sqlStock);

            foreach ($datos['items'] as $item) {
                $stmtDetalle->execute([$idVenta, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']]);
                $stmtStock->execute([$item['cantidad'], $datos['id_sucursal'], $item['id']]);
            }

            // 3. Insertar Pagos
            $sqlPago = "INSERT INTO pago_venta (id_venta, metodo_pago, monto, referencia) VALUES (?, ?, ?, ?)";
            $stmtPago = $this->db->prepare($sqlPago);
            foreach ($datos['pagos'] as $pago) {
                $stmtPago->execute([$idVenta, $pago['metodo'], $pago['monto'], $pago['referencia']]);
            }

            $this->db->commit();
            return $idVenta;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>