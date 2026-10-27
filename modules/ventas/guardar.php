<?php
require_once '../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // 1. Insertar Venta
        $sql = "INSERT INTO venta (id_sucursal, id_cliente, tipo_comprobante, serie, numero, fecha_venta, subtotal, igv, total, estado) 
                VALUES (:sucursal, :cliente, :tipo, :serie, :numero, NOW(), :subtotal, :igv, :total, 'EMITIDA')";
        
        // Generar serie y número (esto debería ser más robusto, pero para empezar)
        $tipo = $_POST['tipo_comprobante'];
        $serie = ($tipo === 'BOLETA') ? 'B001' : 'F001'; 
        $numero = rand(1, 9999); // En producción, obtener el último número

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':sucursal' => $_POST['id_sucursal'],
            ':cliente'  => $_POST['id_cliente'] ?: null,
            ':tipo'     => $tipo,
            ':serie'    => $serie,
            ':numero'   => $numero,
            ':subtotal' => 0, // Se actualizará después
            ':igv'      => 0,
            ':total'    => 0
        ]);
        $idVenta = $pdo->lastInsertId();

        // 2. Insertar Detalle
        $subtotalTotal = 0;
        $sqlDetalle = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                    VALUES (:venta, :producto, :cantidad, :precio, :subtotal)";
        $stmtDetalle = $pdo->prepare($sqlDetalle);

        $productos = $_POST['productos'];
        $cantidades = $_POST['cantidades'];

        foreach ($productos as $index => $idProducto) {
            // Obtener precio actual del producto (seguridad)
            $stmtPrecio = $pdo->prepare("SELECT precio_venta FROM producto WHERE id_producto = ?");
            $stmtPrecio->execute([$idProducto]);
            $precio = $stmtPrecio->fetchColumn();

            $cantidad = $cantidades[$index];
            $subtotal = $precio * $cantidad;
            $subtotalTotal += $subtotal;

            $stmtDetalle->execute([
                ':venta'    => $idVenta,
                ':producto' => $idProducto,
                ':cantidad' => $cantidad,
                ':precio'   => $precio,
                ':subtotal' => $subtotal
            ]);

            // Aquí deberías restar del stock (tabla stock) 
            // $sqlStock = "UPDATE stock SET cantidad = cantidad - ? WHERE id_sucursal = ? AND id_producto = ?";
        }

        // 3. Calcular totales finales
        $igv = $subtotalTotal * 0.18;
        $total = $subtotalTotal + $igv;

        // Actualizar venta con totales
        $pdo->prepare("UPDATE venta SET subtotal = ?, igv = ?, total = ? WHERE id_venta = ?")
            ->execute([$subtotalTotal, $igv, $total, $idVenta]);

        // 4. Insertar Pago
        $stmtPago = $pdo->prepare("INSERT INTO pago_venta (id_venta, metodo_pago, monto) VALUES (?, ?, ?)");
        $stmtPago->execute([$idVenta, $_POST['metodo_pago'], $_POST['monto_pago']]);

        $pdo->commit();
        
        // Redirigir al listado
        header("Location: index.php?success=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error al guardar la venta: " . $e->getMessage();
    }
}
?>