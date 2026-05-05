<?php
/**
 * SISTEMA AZTECA - GESTIÓN DE BARRILES
 * Panel de administración de barriles.
 */
include("../auth/check_auth.php");
include("../../config/conexion.php");

// Consulta para obtener barriles ordenados por ID o número
$sql = "SELECT * FROM barriles ORDER BY id ASC";
$barriles = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
        include("../pages/layout/meta.php");
    ?>
    <title>Configuración de Barriles | Sistema Azteca</title>
    
    <link rel="stylesheet" href="../../assets/css/general.css">
    <link rel="stylesheet" href="../../assets/css/admin_mesas_barril.css">
    
    <?php 
        include("../pages/layout/icons.php");
    ?>
</head>
<body>

<div class="container">
    <header class="header-flex">
        <div>
            <h1>Configuración de Barriles</h1>
        </div>
        <button class="btn-add" onclick="ui.nuevoBarril()" style="background:#27ae60; color:white; padding:12px 24px; border:none; border-radius:8px; cursor:pointer; font-weight:bold;">
            <i class="fas fa-plus"></i> NUEVO BARRIL
        </button>
    </header>

    <div class="grid-barriles">
        <?php if($barriles && $barriles->num_rows > 0): ?>
            <?php while($b = $barriles->fetch_assoc()): ?>
            <div class="barril-box">
                
                <span class="barril-num"><?php echo $b['numero_barril']; ?></span>
                
                <?php 
                    // Normalización de estado para que coincida con las clases del CSS (libre / ocupada)
                    $estado_db = strtolower($b['estado']);
                    $estado_clase = ($estado_db == 'ocupado' || $estado_db == 'ocupada') ? 'ocupada' : 'libre';
                ?>
                <span class="status <?php echo $estado_clase; ?>">
                    <?php echo $b['estado']; ?>
                </span>

                <?php if($estado_clase === 'libre'): ?>
                <div class="actions">
                    <button class="btn-mini btn-edit" title="Editar" onclick='ui.editarBarril(<?php echo json_encode($b); ?>)'>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-mini btn-del" title="Eliminar" onclick="ui.eliminarBarril(<?php echo $b['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <?php else: ?>
                    <div class="actions" style="visibility: hidden;">
                        <button class="btn-mini"><i class="fas fa-lock"></i></button>
                    </div>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 50px; color: #999;">
                <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 10px;"></i>
                <p>No hay barriles registrados.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="modalBarril" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle" style="margin-top:0; color:#8e44ad;">Configurar Barril</h3>
        <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 20px;">
        
        <form action="gestion.php" method="POST">
            <input type="hidden" name="accion" value="guardar_barril">
            <input type="hidden" name="id" id="barril_id">
            
            <div class="form-group">
                <label>Identificador del Barril:</label>
                <input type="text" name="numero_barril" id="barril_num" required placeholder="Ej: B-01 o Barril 1">
                <small style="color: #888;">Este nombre se verá en el panel principal.</small>
            </div>

            <button type="submit" class="btn-save" style="background: #8e44ad;">CONFIRMAR</button>
            <button type="button" class="btn-cancel" onclick="ui.cerrar()">Cancelar</button>
        </form>
    </div>
</div>

<script>
const ui = {
    modal: document.getElementById('modalBarril'),
    
    nuevoBarril() {
        document.getElementById('modalTitle').textContent = "Nuevo Barril";
        document.getElementById('barril_id').value = "";
        document.getElementById('barril_num').value = "";
        this.modal.style.display = 'flex';
    },

    editarBarril(b) {
        document.getElementById('modalTitle').textContent = "Editar " + b.numero_barril;
        document.getElementById('barril_id').value = b.id;
        document.getElementById('barril_num').value = b.numero_barril;
        this.modal.style.display = 'flex';
    },

    cerrar() { 
        this.modal.style.display = 'none'; 
    },

    eliminarBarril(id) {
        if(confirm('¿Estás seguro de eliminar este barril? Esta acción no se puede deshacer.')) {
            window.location.href = `gestion.php?eliminar=true&tabla=barriles&id=${id}`;
        }
    }
}

// Cerrar modal al hacer clic fuera del contenido blanco
window.onclick = function(event) {
    if (event.target == ui.modal) ui.cerrar();
}
</script>
</body>
</html>