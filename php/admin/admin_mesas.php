<?php
/**
 * SISTEMA AZTECA - GESTIÓN DE SALÓN
 * Panel de administración de mesas.
 */
include("../auth/check_auth.php");
include("../../config/conexion.php");


$sql = "SELECT * FROM mesas WHERE id != 99 ORDER BY id ASC";

$mesas = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
        include("../pages/layout/meta.php");
    ?>
    <title>Configuración de Mesas | Sistema Azteca</title>
    <link rel="stylesheet" href="../../assets/css/general.css">
    <link rel="stylesheet" href="../../assets/css/admin_mesas_barril.css">
    <?php 
        include("../pages/layout/icons.php");
    ?>

</head>
<body>

<div class="container">
    <header class="header-flex">
        <h1>Configuración de Mesas</h1>
        <button class="btn-add" onclick="ui.nuevaMesa()" style="background:#27ae60; color:white; padding:12px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
            <i class="fas fa-plus"></i> NUEVA MESA
        </button>
    </header>

    <div class="grid-mesas">
        <?php while($m = $mesas->fetch_assoc()): ?>
        <div class="mesa-box">
            <span class="mesa-num"><?php echo $m['numero_mesa']; ?></span>
            <span class="status <?php echo strtolower($m['estado']); ?>">
                <?php echo $m['estado']; ?>
            </span>

            <?php if(strtolower($m['estado']) === 'libre'): ?>
            <div class="mesa-actions">
                <button class="btn-mini btn-edit" title="Editar número" onclick='ui.editarMesa(<?php echo json_encode($m); ?>)'>
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-mini btn-del" title="Eliminar mesa" onclick="ui.eliminarMesa(<?php echo $m['id']; ?>)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<div id="modalMesa" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0; color:#e67e22;">Configurar Mesa</h3>
        <form action="gestion.php" method="POST">
            <input type="hidden" name="accion" value="guardar_mesa">
            <input type="hidden" name="id" id="mesa_id">
            
            <div class="form-group">
                <label>Número de Mesa:</label>
                <input type="number" name="numero_mesa" id="mesa_num" required min="1" placeholder="Ej: 15">
            </div>

            <button type="submit" class="btn-save">CONFIRMAR</button>
            <button type="button" class="btn-cancel" onclick="ui.cerrar()">Cancelar</button>
        </form>
    </div>
</div>

<script>
const ui = {
    modal: document.getElementById('modalMesa'),
    
    nuevaMesa() {
        document.getElementById('modalTitle').textContent = "Nueva Mesa";
        document.getElementById('mesa_id').value = "";
        document.getElementById('mesa_num').value = "";
        this.modal.style.display = 'flex';
    },

    editarMesa(m) {
        document.getElementById('modalTitle').textContent = "Editar Mesa " + m.numero_mesa;
        document.getElementById('mesa_id').value = m.id;
        document.getElementById('mesa_num').value = m.numero_mesa;
        this.modal.style.display = 'flex';
    },

    cerrar() { this.modal.style.display = 'none'; },

    eliminarMesa(id) {
        if(confirm('¿Confirma la eliminación definitiva de esta mesa?')) {
            window.location.href = `gestion.php?eliminar=true&tabla=mesas&id=${id}`;
        }
    }
}

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    if (event.target == ui.modal) ui.cerrar();
}
</script>
</body>
</html>