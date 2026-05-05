<?php
/**
 * SISTEMA AZTECA - DASHBOARD PRINCIPAL
 * Punto de entrada principal para el personal de salón y delivery.
 * Gestiona la visualización en tiempo real de mesas, barriles y pedidos externos.
 */
include("config/conexion.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
        $title = "Panel de Mesas - AZTECA BAR";
        include("php/pages/layout/meta.php");
    ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    
    <!-- Vinculación de Estilos con control de versiones por tiempo de archivo -->
    <link rel="stylesheet" href="assets/css/index.css?v=<?php echo filemtime('assets/css/index.css'); ?>">

    <!-- Componentes de UI: Iconos -->
    <?php
        include("php/pages/layout/icons.php");
    ?>
</head>
<body>
    <div class="header-top">
        <h1 onclick="abrirModalLogin()" class="header-title">SISTEMA AZTECA BAR</h1>
        <div class="header-spacer"></div> 
    </div>

    <!-- Sección: Gestión de Salón -->
    <h2>Mesas</h2>
    <div class="grid">
        <?php 
            $query = "SELECT m.*, (SELECT id FROM pedidos WHERE mesa_id = m.id AND tipo_entidad = 'mesa' AND estado = 'abierto' LIMIT 1) AS pedido_activo FROM mesas m WHERE m.id != 99 ORDER BY id ASC";   
            $result = $conn->query($query);
            while($m = $result->fetch_assoc()): 
                $esta_ocupada = !empty($m['pedido_activo']);
            ?>
                <a href="php/pages/pedido.php?id=<?php echo $m['id']; ?>&tipo=mesa" class="mesa <?php echo $esta_ocupada ? 'ocupada' : 'libre'; ?>">
                    <?php echo $m['numero_mesa']; ?><br>
                    <span><?php echo $esta_ocupada ? 'OCUPADA' : 'LIBRE'; ?></span>
                </a>
            <?php endwhile; ?>
    </div>

    <!-- Sección: Gestión de Barra -->
    <h2>Barriles</h2>
    <div class="grid">
        <?php 
            $query = "SELECT b.*, (SELECT id FROM pedidos WHERE mesa_id = b.id AND tipo_entidad = 'barril' AND estado = 'abierto' LIMIT 1) AS pedido_activo FROM barriles b ORDER BY id ASC";   
            $result = $conn->query($query);
            while($b = $result->fetch_assoc()): 
                $esta_ocupada = !empty($b['pedido_activo']);
            ?>
                <a href="php/pages/pedido.php?id=<?php echo $b['id']; ?>&tipo=barril" class="mesa <?php echo $esta_ocupada ? 'ocupada' : 'libre'; ?>">
                    <?php echo $b['numero_barril']; ?><br>
                    <span><?php echo $esta_ocupada ? 'OCUPADA' : 'LIBRE'; ?></span>
                </a>
            <?php endwhile; ?>
    </div>

    <!-- Sección: Gestión de Logística de Entrega -->
    <div class="contenedor-delivery">
        <div class="delivery-header">
            <h2>Control de Delivery</h2>
            <button onclick="abrirModal()" class="btn-nuevo-delivery">+ NUEVO</button>
        </div>

        <table class="tabla-azteca">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Origen</th>
                    <th>Estado Actual</th>
                    <th>Total</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT d.* FROM pedidos_delivery d WHERE d.estado != 'Finalizado' ORDER BY d.id_delivery DESC";
                $res = $conn->query($sql);
                if($res && $res->num_rows > 0):
                    while($d = $res->fetch_assoc()):
                        $clase_origen = (stripos($d['origen'], 'pedidos') !== false) ? 'badge-pya' : 'badge-wa';
                        $nombre_origen = (stripos($d['origen'], 'pedidos') !== false) ? 'PEDIDOS YA' : 'WHATSAPP';
                        
                        $estado_texto = strtolower($d['estado']);
                        $clase_estado = 'estado-default';
                        if (stripos($estado_texto, 'pendiente') !== false) {
                            $clase_estado = 'estado-pendiente';
                        } elseif (stripos($estado_texto, 'cocina') !== false) {
                            $clase_estado = 'estado-cocina';
                        }
                ?>
                <tr>
                    <td data-label="Pedido" class="td-id-gestion">
                        <strong>#<?php echo $d['id_delivery']; ?></strong>
                        <a href="php/pages/pedido.php?id=99&tipo=delivery&pedido_id=<?php echo $d['id_delivery']; ?>&origen=<?php echo $d['origen']; ?>" class="btn-editar btn-mobile-only">GESTIONAR</a>
                    </td>
                    <td data-label="Origen"><span class="<?php echo $clase_origen; ?>"><?php echo $nombre_origen; ?></span></td>
                    <td data-label="Estado Actual" class="<?php echo $clase_estado; ?>">
                        <span class="status-indicator">●</span> <?php echo $d['estado']; ?>
                    </td>
                    <td data-label="Total"><strong>$<?php echo number_format($d['monto_total'], 0, ',', '.'); ?></strong></td>
                    <td class="td-desktop-only">
                        <a href="php/pages/pedido.php?id=99&tipo=delivery&pedido_id=<?php echo $d['id_delivery']; ?>&origen=<?php echo $d['origen']; ?>" class="btn-editar">GESTIONAR</a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5" class="no-data">No hay registros de delivery activos</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Modales de Interacción -->
    <div id="modalDelivery" class="modal-azteca">
        <div class="modal-content">
            <h3>Seleccionar Origen del Pedido</h3>
            <button class="btn-modal bg-whatsapp" onclick="irAPedido('WhatsApp')">WHATSAPP</button>
            <button class="btn-modal bg-pedidosya" onclick="irAPedido('PedidosYa')">PEDIDOS YA</button>
            <hr>
            <button class="btn-modal bg-cancel btn-half" onclick="cerrarModal()">CANCELAR</button>
        </div>
    </div>

    <div id="modalLogin" class="modal-azteca">
        <div class="modal-content modal-login-config">
            <h3 class="color-azteca">LOGIN DE ADMINISTRACIÓN</h3>
            <div id="errorLogin" class="error-box"></div>
            <form id="formLogin">
                <div class="form-group-admin">
                    <label>Usuario</label>
                    <input type="text" name="usuario" id="user_admin" class="input-admin" required>
                </div>
                <div class="form-group-admin">
                    <label>Contraseña</label>
                    <input type="password" name="password" class="input-admin" required>
                </div>
                <button type="submit" class="btn-modal bg-azteca btn-full">Entrar</button>
                <button type="button" class="btn-modal bg-cancel btn-full" onclick="cerrarModalLogin()">Cancelar</button>
            </form>
        </div>
    </div>

    <script>
        /**
         * Controladores de interfaz de usuario para Ventanas Modales
         */
        const abrirModal = () => document.getElementById('modalDelivery').style.display = 'flex';
        const cerrarModal = () => document.getElementById('modalDelivery').style.display = 'none';
        const abrirModalLogin = () => { 
            document.getElementById('modalLogin').style.display = 'flex'; 
            document.getElementById('user_admin').focus(); 
        };
        const cerrarModalLogin = () => { 
            document.getElementById('modalLogin').style.display = 'none'; 
            document.getElementById('errorLogin').style.display = 'none'; 
        };

        /**
         * Procesamiento de Autenticación mediante AJAX
         */
        document.getElementById('formLogin').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            try {
                const resp = await fetch('php/admin/login.php', { method: 'POST', body: fd });
                const res = await resp.json();
                if (res.success) { window.location.href = 'php/admin/dashboard.php'; } 
                else { 
                    const err = document.getElementById('errorLogin');
                    err.textContent = res.message;
                    err.style.display = 'block';
                }
            } catch (e) { alert("Error de comunicación con el servidor"); }
        });

        /**
         * Generación de nuevas instancias de pedidos delivery
         */
        async function irAPedido(origen) {
            const fd = new FormData();
            fd.append('mesa_id', 99); 
            fd.append('tipo', 'delivery');
            fd.append('origen', origen); 
            fd.append('accion', 'crear_vacio'); 
            try {
                const resp = await fetch('php/actions/actualizar_item.php', { method: 'POST', body: fd });
                const pedidoId = (await resp.text()).trim();
                if (!isNaN(pedidoId) && parseInt(pedidoId) > 0) {
                    window.location.href = `php/pages/pedido.php?id=99&tipo=delivery&origen=${origen}&pedido_id=${pedidoId}`;
                }
            } catch (e) { alert("Error de conexión al procesar el pedido"); }
        }
    </script>
</body>
</html>