<p style="margin-bottom:14px"><a class="btn g" href="productos.php?m=productos">← Volver a productos</a></p>

<div class="tw">
    <div class="tools"><h3>Historial de precios: <?= e($producto['nombre']) ?> <span class="tag b"><?= count($historial) ?></span></h3></div>
    <div class="scroll">
        <table>
            <thead><tr><th>Precio</th><th>Desde</th><th>Hasta</th></tr></thead>
            <tbody>
            <?php foreach ($historial as $h): ?>
                <tr>
                    <td><b>S/ <?= number_format((float)$h['precio'], 2) ?></b></td>
                    <td><?= e($h['fecha_inicio']) ?></td>
                    <td><?= $h['fecha_fin'] ? e($h['fecha_fin']) : '<span class="tag ok">Vigente</span>' ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (!$historial): ?><div class="empty">Sin registros de precio</div><?php endif; ?>
    </div>
</div>