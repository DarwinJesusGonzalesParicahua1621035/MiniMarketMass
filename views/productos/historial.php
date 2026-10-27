<h2>Historial de precios: <?= e($producto['nombre']) ?></h2>
<p><a href="productos.php?m=productos">← Volver a productos</a></p>
<table>
    <tr><th>Precio</th><th>Desde</th><th>Hasta</th></tr>
    <?php foreach ($historial as $h): ?>
    <tr>
        <td>S/ <?= number_format((float)$h['precio'], 2) ?></td>
        <td><?= e($h['fecha_inicio']) ?></td>
        <td><?= $h['fecha_fin'] ? e($h['fecha_fin']) : 'Vigente' ?></td>
    </tr>
    <?php endforeach; ?>
</table>