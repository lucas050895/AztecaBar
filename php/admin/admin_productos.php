<?php
/**
 * SISTEMA AZTECA - MÓDULO DE INVENTARIO Y PROMOCIONES
 * Gestión de catálogo de productos y actualización masiva de precios para empanadas.
 */

include("../auth/check_auth.php");
include("../../config/conexion.php");

// Procesamiento de actualización masiva de precios
$mensaje_promo = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['p_unidad'])) {
    $p_unidad = mysqli_real_escape_string($conn, $_POST['p_unidad']);
    $p_docena = mysqli_real_escape_string($conn, $_POST['p_docena']);

    // Actualización de valores de referencia en tabla de promociones
    $conn->query("UPDATE promo_empanadas SET valor = '$p_unidad' WHERE clave = 'precio_unidad'");
    $conn->query("UPDATE promo_empanadas SET valor = '$p_docena' WHERE clave = 'precio_docena'");

    // Sincronización masiva de precios en tabla productos mediante subconsulta
    $sql_update_prods = "UPDATE productos 
                         SET precio = '$p_unidad' 
                         WHERE subcategoria_id IN (
                            SELECT id FROM subcategorias WHERE nombre = 'Empanadas'
                         )";
    
    $mensaje_promo = ($conn->query($sql_update_prods)) ? "ok" : "error";
}

// Carga de catálogo con relación de subcategorías
$sql_productos = "SELECT p.id, p.nombre, p.precio, p.es_cocina, p.subcategoria_id, s.nombre as cat_nombre 
                  FROM productos p 
                  LEFT JOIN subcategorias s ON p.subcategoria_id = s.id 
                  GROUP BY p.id 
                  ORDER BY s.nombre ASC, p.nombre ASC";
$res_productos = $conn->query($sql_productos);

// Carga de subcategorías para componentes select
$sql_categorias = "SELECT id, nombre FROM subcategorias GROUP BY nombre ORDER BY nombre ASC";
$res_categorias = $conn->query($sql_categorias);

// Carga de estados actuales de precios promocionales
$res_promo_vals = $conn->query("SELECT * FROM promo_empanadas");
$p_promo = [];
while($r = $res_promo_vals->fetch_assoc()) {
    $p_promo[$r['clave']] = $r['valor'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
        $title = "Inventario | Sistema Azteca";
        include("../pages/layout/meta.php");
    ?>
    <link rel="stylesheet" href="../../assets/css/general.css">
    <link rel="stylesheet" href="../../assets/css/admin_productos.css">
    <?php include("../pages/layout/icons.php"); ?>
</head>
<body>

<div class="container">
    <header class="header-flex">
        <h1>Administración General</h1>
        <nav class="header-actions">
            <button class="btn-add" onclick="ui.abrirModal()">+ NUEVO PRODUCTO</button>
        </nav>
    </header>

    <nav class="tabs-admin">
        <div class="tab-item active" onclick="ui.cambiarTab('productos', this)">Productos</div>
        <div class="tab-item" onclick="ui.cambiarTab('promos', this)">Precios Empanadas</div>
    </nav>

    <div id="content-productos">
        <section class="search-container">
            <input type="text" id="inputBusqueda" class="search-input" 
                   placeholder="Buscar por nombre de producto o categoría" 
                   onkeyup="ui.filtrarTabla()">
        </section>

        <main class="table-container">
            <table class="tabla-prod" id="tablaProductos">
                <thead>
                    <tr>
                        <th>Categoría</th>
                        <th>Nombre</th>
                        <th>Cocina</th>
                        <th>Precio</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($p = $res_productos->fetch_assoc()): ?>
                    <tr class="fila-producto" id="fila-<?php echo $p['id']; ?>">
                        <td class="col-categoria"><span class="cat-tag"><?php echo htmlspecialchars($p['cat_nombre']); ?></span></td>
                        <td class="col-nombre"><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                        <td><?php echo $p['es_cocina'] ? 'Si' : 'No'; ?></td>
                        <td>$<?php echo number_format($p['precio'], 2, ',', '.'); ?></td>
                        <td style="text-align: right;">
                            <button class="btn-edit" onclick='ui.editar(<?php echo json_encode($p); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn-del" onclick="ui.eliminar(<?php echo $p['id']; ?>)">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </main>
    </div>

    <div id="content-promos" style="display: none;">
        <?php if($mensaje_promo === "ok"): ?>
            <div class="alerta-promo alerta-ok">¡Éxito! Precios actualizados en todo el sistema.</div>
        <?php elseif($mensaje_promo === "error"): ?>
            <div class="alerta-promo alerta-err">Error al actualizar los precios. Verifique la base de datos.</div>
        <?php endif; ?>

        <div class="card-promo">
            <h2>Precios Masivos</h2>       
            <form method="POST">
                <div class="form-group">
                    <label>Precio por Unidad:</label>
                    <input type="number" name="p_unidad" step="0.01" required placeholder="$12345">
                </div>
                
                <div class="form-group">
                    <label>Precio por Docena:</label>
                    <input type="number" name="p_docena" step="0.01" required placeholder="$12345">
                </div>
                
                <button type="submit" class="btn-update-all">Actualizar Todo</button>
            </form>
        </div>
    </div>
</div>

<aside id="modalProducto" class="modal-overlay">
    <div class="modal-content">
        <h2 id="modalTitle">Detalles del Producto</h2>
        <form action="admin_acciones.php" method="POST">
            <input type="hidden" name="accion" value="guardar_producto">
            <input type="hidden" name="id" id="prod_id">
            
            <div class="form-group">
                <label for="prod_nombre">Nombre del Producto</label>
                <input type="text" name="nombre" id="prod_nombre" required>
            </div>

            <div class="form-group">
                <label for="prod_cat">Categoría</label>
                <select name="subcategoria_id" id="prod_cat" required>
                    <option value="" disabled selected>Seleccione una opción</option>
                    <?php 
                    $res_categorias->data_seek(0);
                    while($c = $res_categorias->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="prod_cocina">¿Es de Cocina?</label>
                <select name="es_cocina" id="prod_cocina" required>
                    <option value="1">Si</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="form-group">
                <label for="prod_precio">Precio de Venta</label>
                <input type="number" name="precio" id="prod_precio" step="0.01" required>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-submit">CONFIRMAR</button>
                <button type="button" class="btn-cancel" onclick="ui.cerrarModal()">CANCELAR</button>
            </div>
        </form>
    </div>
</aside>

<script>
/**
 * Controlador para la gestión dinámica de la interfaz.
 */
const ui = {
    modal: document.getElementById('modalProducto'),

    cambiarTab(target, btn) {
        document.getElementById('content-productos').style.display = (target === 'productos') ? 'block' : 'none';
        document.getElementById('content-promos').style.display = (target === 'promos') ? 'block' : 'none';
        document.querySelector('.btn-add').style.visibility = (target === 'productos') ? 'visible' : 'hidden';

        document.querySelectorAll('.tab-item').forEach(i => i.classList.remove('active'));
        btn.classList.add('active');
    },

    filtrarTabla() {
        const query = document.getElementById('inputBusqueda').value.toLowerCase();
        const filas = document.querySelectorAll('.fila-producto');

        filas.forEach(fila => {
            const nombre = fila.querySelector('.col-nombre').textContent.toLowerCase();
            const categoria = fila.querySelector('.col-categoria').textContent.toLowerCase();
            fila.style.display = (nombre.includes(query) || categoria.includes(query)) ? "" : "none";
        });
    },

    abrirModal() {
        document.getElementById('modalTitle').textContent = "Nuevo Producto";
        document.getElementById('prod_id').value = "";
        document.querySelector('#modalProducto form').reset();
        document.getElementById('prod_cocina').value = "0";
        this.modal.style.display = 'flex';
    },

    cerrarModal() {
        this.modal.style.display = 'none';
    },

    editar(p) {
        document.getElementById('modalTitle').textContent = "Modificar Producto";
        document.getElementById('prod_id').value = p.id;
        document.getElementById('prod_nombre').value = p.nombre;
        document.getElementById('prod_precio').value = p.precio;
        document.getElementById('prod_cat').value = p.subcategoria_id;
        document.getElementById('prod_cocina').value = p.es_cocina;
        this.modal.style.display = 'flex';
    },

    async eliminar(id) {
        if (!confirm("¿Confirma la eliminación definitiva de este producto?")) return;
        
        const fd = new FormData();
        fd.append('id', id);
        fd.append('accion', 'eliminar_producto');

        try {
            const resp = await fetch('admin_acciones.php', { method: 'POST', body: fd });
            const result = await resp.text();
            if (result.trim() === "ok") {
                document.getElementById(`fila-${id}`).remove();
            }
        } catch (error) {
            console.error("Error en la solicitud de eliminación:", error);
        }
    }
};

<?php if($mensaje_promo !== ""): ?>
    ui.cambiarTab('promos', document.querySelectorAll('.tab-item')[1]);
<?php endif; ?>
</script>

</body>
</html>