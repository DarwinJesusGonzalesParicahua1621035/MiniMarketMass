<?php
require_once '../../config/database.php';

// Consulta para traer las ventas con datos legibles
$sql = "SELECT v.id_venta, s.nombre AS sucursal, v.tipo_comprobante, 
            v.serie, v.numero, c.nombre AS cliente, 
            v.fecha_venta, v.subtotal, v.igv, v.total, v.estado
        FROM venta v
        JOIN sucursal s ON v.id_sucursal = s.id_sucursal
        LEFT JOIN cliente c ON v.id_cliente = c.id_cliente
        ORDER BY v.fecha_venta DESC";
$stmt = $pdo->query($sql);
$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ventas - Minimarket Mass</title>
    <!-- Aquí deberías incluir los estilos del panel que te pasaron -->
</head>
<body>
    <h2>Gestión de Ventas</h2>
    <a href="crear.php" class="btn">+ Nueva Venta</a>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Comprobante</th>
                <th>Sucursal</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Total</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ventas as $v): ?>
            <tr>
                <td><?= $v['id_venta'] ?></td>
                <td><?= $v['tipo_comprobante'] ?> <?= $v['serie'] ?>-<?= $v['numero'] ?></td>
                <td><?= $v['sucursal'] ?></td>
                <td><?= $v['cliente'] ?? '—' ?></td>
                <td><?= $v['fecha_venta'] ?></td>
                <td>S/ <?= number_format($v['total'], 2) ?></td>
                <td><span class="tag <?= $v['estado'] === 'EMITIDA' ? 'ok' : 'bad' ?>"><?= $v['estado'] ?></span></td>
                <td><a href="ver.php?id=<?= $v['id_venta'] ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
