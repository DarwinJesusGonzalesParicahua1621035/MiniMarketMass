<!-- views/ventas/pos.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Punto de Venta - Mass</title>
    <!-- Pega aquí todo el CSS que tenías en tu archivo HTML -->
    <style>
        /* ... Tu CSS ... */
    </style>
</head>
<body>
    <!-- ... Tu estructura HTML (aside, main, etc.) ... -->
    <div id="view"></div>

    <script>
        // 1. INYECTAMOS LOS DATOS DE PHP A JAVASCRIPT
        const PRODUCTOS_DB = <?= json_encode($productos) ?>;
        
        // Estructura adaptada para que tu JS actual siga funcionando
        const D = {
            producto: PRODUCTOS_DB.map(p => [
                p.id_producto, 
                p.codigo_barras, 
                p.nombre, 
                p.categoria, 
                p.unidad_medida, 
                parseFloat(p.precio_venta), 
                1 // activo
            ]),
            stock: {}, // Lo llenamos abajo
            // ... (aquí puedes dejar tus clientes de prueba o cargarlos de BD)
            cliente: [[1,'DNI','72845619','Carlos Quispe Mamani']], 
            corr: {BOLETA: 233, FACTURA: 12}, // Esto debería venir de la BD
            sesion: {id: 1, estado: 'ABIERTA', inicial: 100}
        };

        // Llenar el objeto stock basado en la BD
        PRODUCTOS_DB.forEach(p => {
            D.stock[p.id_producto] = parseInt(p.stock) || 0;
        });

        // 2. MODIFICAMOS LA FUNCIÓN confirmPay() PARA QUE GUARDE EN LA BASE DE DATOS
        function confirmPay() {
            const s = payState();
            if (!s.ok) return;

            // Preparamos el paquete de datos para enviar a PHP
            const ventaData = {
                id_sucursal: 1,
                id_cliente: null, // Aquí deberías buscar el ID del cliente si lo ingresaron
                tipo_comprobante: comprobante,
                serie: comprobante === 'BOLETA' ? 'B001' : 'F001',
                numero: D.corr[comprobante],
                subtotal: s.T / 1.18,
                igv: s.T - (s.T / 1.18),
                total: s.T,
                items: cart.map(l => ({
                    id: l.id,
                    cantidad: l.qty,
                    precio: l.pu,
                    subtotal: l.tl
                })),
                pagos: P.map(p => ({
                    metodo: p.m,
                    monto: parseFloat(p.monto),
                    referencia: p.ref || ''
                }))
            };

            // Enviamos por AJAX (Fetch API) al controlador
            fetch('/controllers/VentaController.php?action=guardar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(ventaData)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    toast('Venta registrada con éxito');
                    // Actualizar stock local, limpiar carrito, imprimir ticket...
                    D.corr[comprobante]++;
                    cart.forEach(l => D.stock[l.id] -= l.qty);
                    cart = [];
                    close_();
                    renderPOS();
                    // Aquí puedes llamar a tu función showTicket()
                } else {
                    toast('Error al guardar: ' + data.message, true);
                }
            })
            .catch(err => toast('Error de conexión', true));
        }

        // ... (El resto de tu código JavaScript se mantiene igual) ...
    </script>
</body>
</html>