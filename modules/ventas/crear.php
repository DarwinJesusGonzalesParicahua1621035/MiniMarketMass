<?php
require_once '../../config/database.php';

// Obtener sucursales y productos para los selects
$sucursales = $pdo->query("SELECT id_sucursal, nombre FROM sucursal")->fetchAll();
$productos = $pdo->query("SELECT id_producto, nombre, precio_venta FROM producto WHERE activo = 1")->fetchAll();
$clientes = $pdo->query("SELECT id_cliente, nombre FROM cliente")->fetchAll();
?>

<h2>Registrar Nueva Venta</h2>
<form action="guardar.php" method="POST" id="formVenta">
    <!-- Datos generales -->
    <label>Sucursal:</label>
    <select name="id_sucursal" required>
        <?php foreach ($sucursales as $s): ?>
            <option value="<?= $s['id_sucursal'] ?>"><?= $s['nombre'] ?></option>
        <?php endforeach; ?>
    </select>

    <label>Cliente:</label>
    <select name="id_cliente">
        <option value="">— Sin cliente (Boleta) —</option>
        <?php foreach ($clientes as $c): ?>
            <option value="<?= $c['id_cliente'] ?>"><?= $c['nombre'] ?></option>
        <?php endforeach; ?>
    </select>

    <label>Tipo Comprobante:</label>
    <select name="tipo_comprobante" required>
        <option value="BOLETA">Boleta</option>
        <option value="FACTURA">Factura</option>
    </select>

    <!-- Detalle de productos (dinámico) -->
    <h3>Productos</h3>
    <table id="tablaProductos">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Subtotal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <!-- Filas agregadas por JS -->
        </tbody>
    </table>
    <button type="button" onclick="agregarFila()">+ Agregar Producto</button>

    <!-- Totales -->
    <div>
        <p>Subtotal: S/ <span id="subtotal">0.00</span></p>
        <p>IGV (18%): S/ <span id="igv">0.00</span></p>
        <p><strong>Total: S/ <span id="total">0.00</span></strong></p>
    </div>

    <!-- Pago -->
    <h3>Pago</h3>
    <label>Método de pago:</label>
    <select name="metodo_pago" required>
        <option value="EFECTIVO">Efectivo</option>
        <option value="YAPE">Yape</option>
        <option value="TARJETA">Tarjeta</option>
    </select>
    <label>Monto:</label>
    <input type="number" step="0.01" name="monto_pago" required>

    <button type="submit">Guardar Venta</button>
</form>

<script>
// Lógica para agregar productos y calcular totales
const productos = <?= json_encode($productos) ?>;

function agregarFila() {
    const tbody = document.querySelector('#tablaProductos tbody');
    const tr = document.createElement('tr');
    
    let options = productos.map(p => `<option value="${p.id_producto}" data-precio="${p.precio_venta}">${p.nombre} (S/ ${p.precio_venta})</option>`).join('');
    
    tr.innerHTML = `
        <td><select name="productos[]" onchange="calcular()">${options}</select></td>
        <td class="precio">0.00</td>
        <td><input type="number" name="cantidades[]" value="1" min="1" onchange="calcular()"></td>
        <td class="subtotal-fila">0.00</td>
        <td><button type="button" onclick="this.closest('tr').remove(); calcular()">X</button></td>
    `;
    tbody.appendChild(tr);
    calcular();
}

function calcular() {
    let subtotal = 0;
    document.querySelectorAll('#tablaProductos tbody tr').forEach(tr => {
        const select = tr.querySelector('select');
        const precio = parseFloat(select.options[select.selectedIndex].dataset.precio) || 0;
        const cantidad = parseInt(tr.querySelector('input[name="cantidades[]"]').value) || 0;
        const subFila = precio * cantidad;
        
        tr.querySelector('.precio').textContent = precio.toFixed(2);
        tr.querySelector('.subtotal-fila').textContent = subFila.toFixed(2);
        subtotal += subFila;
    });

    const igv = subtotal * 0.18;
    const total = subtotal + igv;

    document.getElementById('subtotal').textContent = subtotal.toFixed(2);
    document.getElementById('igv').textContent = igv.toFixed(2);
    document.getElementById('total').textContent = total.toFixed(2);
}
</script>