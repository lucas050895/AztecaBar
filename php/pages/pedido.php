<?php
/**
 * SISTEMA AZTECA - MÓDULO DE GESTIÓN DE PEDIDOS (POS)
 * Gestión dinámica de productos, promociones y variantes por porción.
 */
include("../../config/conexion.php");

// Saneamiento de variables de instancia
$mesa_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'mesa'; 
$pedido_id_especifico = isset($_GET['pedido_id']) ? intval($_GET['pedido_id']) : 0;
$origen_delivery = isset($_GET['origen']) ? $_GET['origen'] : '';

if ($mesa_id == 0 && $pedido_id_especifico == 0) die("Error: ID de entidad no definido.");

/**
 * CONFIGURACIÓN DE PROMOCIONES DESDE DB
 */
$conf_promo = $conn->query("SELECT clave, valor FROM promo_empanadas");
$PRECIOS_PROMO = [];
while($r = $conf_promo->fetch_assoc()) {
    $PRECIOS_PROMO[$r['clave']] = $r['valor'];
}

$p_unidad_val = $PRECIOS_PROMO['precio_unidad'] ?? 0;
$p_docena_val = $PRECIOS_PROMO['precio_docena'] ?? 0;

/**
 * GESTIÓN DE PORCIONES
 */
$res_porciones = $conn->query("SELECT id, producto_id, nombre_variante, precio FROM productos_porcion");
$porciones_data = [];
if ($res_porciones) {
    while($por = $res_porciones->fetch_assoc()) {
        $porciones_data[$por['producto_id']][] = [
            'nombre_variante' => $por['nombre_variante'],
            'precio' => (float)$por['precio']
        ];
    }
}

/**
 * GESTIÓN DE CATÁLOGO
 */
$res_secciones = $conn->query("SELECT * FROM categorias_secciones ORDER BY id ASC");
$secciones = [];
while($s = $res_secciones->fetch_assoc()) $secciones[] = $s;

$sql_menu = "SELECT p.*, s.nombre as subcategoria, s.id as sub_id, cs.id as seccion_id 
            FROM productos p
            JOIN subcategorias s ON p.subcategoria_id = s.id
            JOIN categorias_secciones cs ON s.seccion_id = cs.id
            ORDER BY p.nombre ASC";
$res_menu = $conn->query($sql_menu);
$productos_data = [];
$subcategorias_unicas = [];
while($p = $res_menu->fetch_assoc()) {
    $productos_data[] = $p;
    $subcategorias_unicas[$p['seccion_id']][$p['sub_id']] = $p['subcategoria'];
}

/**
 * PERSISTENCIA DE ESTADO Y RECUPERACIÓN DE ITEMS
 */
$items_viejos = [];
$monto_total_db = 0;
$id_para_js = 0;
$direccion_actual = ""; 

if ($tipo === 'delivery' && $pedido_id_especifico > 0) {
    $where_clause = "WHERE ped.id = $pedido_id_especifico";
    $id_para_js = $pedido_id_especifico;
    $res_dir = $conn->query("SELECT direccion FROM pedidos_delivery WHERE id_delivery = $pedido_id_especifico");
    if($row_dir = $res_dir->fetch_assoc()) $direccion_actual = $row_dir['direccion'];
} else {
    $where_clause = "WHERE ped.mesa_id = $mesa_id AND ped.tipo_entidad = '$tipo' AND ped.estado = 'abierto'";
}

$sql_items = "SELECT pd.producto_id, p.nombre, pd.precio_unitario, s.nombre as subcategoria, pd.impreso, ped.monto_total, ped.id as pedido_real_id
              FROM pedido_detalle pd 
              JOIN productos p ON pd.producto_id = p.id 
              JOIN subcategorias s ON p.subcategoria_id = s.id
              JOIN pedidos ped ON pd.pedido_id = ped.id 
              $where_clause ORDER BY pd.id ASC";

$res_items = $conn->query($sql_items);
if($res_items && $res_items->num_rows > 0){
    while($row = $res_items->fetch_assoc()) {
        $id_para_js = $row['pedido_real_id']; 
        $monto_total_db = $row['monto_total'];
        $items_viejos[] = [
            'id' => (string)$row['producto_id'], 
            'nombre' => $row['nombre'], 
            'precio' => (float)$row['precio_unitario'], 
            'categoria' => $row['subcategoria'], 
            'impreso' => (int)$row['impreso']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php 
        $title = "Pedido";
        include("layout/meta.php"); 
    ?>
    <link rel="stylesheet" href="../../assets/css/pedido.css?v=<?php echo filemtime('../../assets/css/pedido.css'); ?>">
    <?php include("layout/icons.php"); ?>
</head>
<body>

<div class="container no-print">
    <div class="menu-seccion">
        <h2 class="titulo-entidad">
            <?php echo ($tipo === 'delivery') ? "DELIVERY - $origen_delivery (#$id_para_js)" : strtoupper($tipo)." #$mesa_id"; ?>
        </h2>
        
        <?php if($tipo === 'delivery'): ?>
        <div class="direccion-delivery-box">
            <label class="label-delivery">DIRECCIÓN DE ENTREGA:</label>
            <input type="text" id="direccion_input" placeholder="Ingrese dirección..." class="input-delivery" onchange="actualizarDireccion()" value="<?php echo htmlspecialchars($direccion_actual); ?>">
        </div>
        <?php endif; ?>

        <div class="tabs-header">
            <?php foreach($secciones as $i => $sec): ?>
                <button class="tab-btn <?php echo $i===0?'active':''; ?>" onclick="cambiarTab(event, 'tab-<?php echo $sec['id']; ?>')">
                    <?php echo strtoupper($sec['nombre']); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <?php foreach($secciones as $i => $sec): ?>
            <div id="tab-<?php echo $sec['id']; ?>" class="tab-content" style="<?php echo $i===0?'display:block':'display:none'; ?>">
                <select class="filtro-subcat" onchange="filtrarPorSubcategoria(this.value, 'grid-<?php echo $sec['id']; ?>')">
                    <option value="todas">MOSTRAR TODO</option>
                    <?php if(isset($subcategorias_unicas[$sec['id']])): 
                        foreach($subcategorias_unicas[$sec['id']] as $id_sub => $nom_sub): ?>
                        <option value="sub-<?php echo $id_sub; ?>"><?php echo strtoupper($nom_sub); ?></option>
                    <?php endforeach; endif; ?>
                </select>
                <div id="grid-<?php echo $sec['id']; ?>" class="productos-grid">
                    <?php foreach($productos_data as $pr): if($pr['seccion_id'] == $sec['id']): 
                        $tiene_porciones = isset($porciones_data[$pr['id']]);
                    ?>
                        <div class="card-prod item-producto sub-<?php echo $pr['sub_id']; ?>" 
                             onclick="clickProducto('<?php echo $pr['id']; ?>', '<?php echo addslashes($pr['nombre']); ?>', <?php echo $pr['precio']; ?>, '<?php echo $pr['subcategoria']; ?>')">
                            
                            <strong><?php echo $pr['nombre']; ?></strong><br>
                            
                            <?php if(!$tiene_porciones): ?>
                                <span class="precio-tag">$<?php echo number_format($pr['precio'], 0, ',', '.'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <button class="btn-accion bg-volver" onclick="window.location.href='../../index.php'">VOLVER AL INICIO</button>
    </div>

    <div class="pedido-seccion">
        <h2 class="titulo-resumen">Resumen</h2>
        <div id="lista-items"></div> 
        
        <div class="footer-pedido">
            <div id="contenedor-descuento"></div>
            <div id="total-pedido">Total: $<?php echo number_format($monto_total_db, 0, ',', '.'); ?></div>
            <textarea id="observaciones" placeholder="Observaciones para cocina..."></textarea>
            
            <button class="btn-accion bg-cocina" onclick="imprimirComanda()">
                <i class="fas fa-print"></i> ENVIAR A COCINA
            </button>
            
            <button class="btn-accion bg-finalizar" onclick="cerrarCaja()">
                <i class="fas fa-check-circle"></i> FINALIZAR <?php echo strtoupper($tipo); ?>
            </button>
        </div>
    </div>
</div>

<div id="modal-porcion" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h3 id="modal-titulo-prod">Seleccione una opción</h3>
        <div id="opciones-porcion-container" class="grid-porciones"></div>
        <button class="btn-accion bg-volver" onclick="cerrarModalPorcion()">CANCELAR</button>
    </div>
</div>

<script>
    let items_raw = <?php echo json_encode($items_viejos); ?>;
    let porciones_db = <?php echo json_encode($porciones_data); ?>;
    let ID_REFERENCIA = <?php echo $id_para_js; ?>;
    const MESA_ID = <?php echo $mesa_id; ?>, TIPO = '<?php echo $tipo; ?>', ORIGEN = '<?php echo $origen_delivery; ?>';
    const PRECIO_UNITARIO_REF = parseFloat("<?php echo $p_unidad_val; ?>");
    const PRECIO_DOCENA_REF = parseFloat("<?php echo $p_docena_val; ?>");

    function clickProducto(id, nombre, precio, categoria) {
        const opciones = porciones_db[id];
        if (opciones && opciones.length > 0) {
            abrirModalPorcion(id, nombre, categoria, opciones);
        } else {
            ejecutarAgregado(id, nombre, precio, categoria);
        }
    }

    function formatearNombreVariante(nombre) {
        const n = nombre.toUpperCase().trim();
        if (n === "1 P") return "1 PORCIÓN";
        if (n === "2 P") return "2 PORCIONES";
        return n; 
    }

    function abrirModalPorcion(id, nombre, cat, opciones) {
        const container = document.getElementById('opciones-porcion-container');
        document.getElementById('modal-titulo-prod').innerText = nombre.toUpperCase();
        container.innerHTML = '';
        
        opciones.forEach(op => {
            const nombreLargo = formatearNombreVariante(op.nombre_variante);
            const btn = document.createElement('button');
            btn.className = 'btn-porcion-opcion';
            
            btn.innerHTML = `
                <span class="op-nombre">${nombreLargo}</span>
                <span class="op-precio">$${op.precio.toLocaleString()}</span>
            `;
            btn.onclick = () => {
                ejecutarAgregado(id, `${nombre} (${nombreLargo})`, op.precio, cat);
                cerrarModalPorcion();
            };
            container.appendChild(btn);
        });
        document.getElementById('modal-porcion').style.display = 'flex';
    }

    function cerrarModalPorcion() { 
        document.getElementById('modal-porcion').style.display = 'none'; 
    }

    async function ejecutarAgregado(id, n, p, c) {
        items_raw.push({ id: String(id), nombre: n, precio: p, categoria: c, impreso: 0 });
        render(); 
        await syncDB('add', id, p);
    }

    function render() {
        const lista = document.getElementById('lista-items');
        lista.innerHTML = '';
        let totalBruto = 0, cantEmpanadas = 0;
        const agrupado = {};

        items_raw.forEach(item => {
            const idKey = item.nombre; 
            if(!agrupado[idKey]) agrupado[idKey] = { ...item, cantidad: 0, cant_pendientes: 0, cant_impresos: 0 };
            agrupado[idKey].cantidad++;
            if(item.impreso === 1) agrupado[idKey].cant_impresos++; else agrupado[idKey].cant_pendientes++;
        });

        const porCat = Object.values(agrupado).reduce((acc, i) => { 
            if(!acc[i.categoria]) acc[i.categoria] = []; 
            acc[i.categoria].push(i); 
            return acc; 
        }, {});

        for (const cat in porCat) {
            const h = document.createElement('div'); h.className = 'pedido-categoria-header'; h.innerText = cat.toUpperCase(); lista.appendChild(h);
            porCat[cat].forEach(item => {
                totalBruto += (item.precio * item.cantidad);
                if(cat.toLowerCase() === "empanadas") cantEmpanadas += item.cantidad;
                
                const row = document.createElement('div'); row.className = 'item-row';
                const todoImp = item.cant_pendientes === 0;
                const badge = todoImp ? `<span class="status-badge status-impreso">En Cocina</span>` : `<span class="status-badge status-pendiente">Pendiente: ${item.cant_pendientes}</span>`;
                
                row.innerHTML = `
                    <div class="item-row-header">
                        <div class="item-info">
                            <strong>${item.nombre}</strong>
                            <div class="item-status-container">${badge}</div>
                        </div>
                        <button class="btn-x" ${todoImp?'disabled':''} onclick="eliminarProductoCompleto('${item.nombre}')">×</button>
                    </div>
                    <div class="item-controls">
                        <span>$${item.precio.toLocaleString()} x</span>
                        <input type="number" class="input-cant" value="${item.cantidad}" min="${item.cant_impresos}" onchange="modificarCantidad('${item.id}', '${item.nombre}', this.value)">
                        <b class="subtotal-item">$${(item.precio * item.cantidad).toLocaleString()}</b>
                    </div>`;
                lista.appendChild(row);
            });
        }
        
        let desc = 0;
        if (PRECIO_UNITARIO_REF > 0 && PRECIO_DOCENA_REF > 0 && cantEmpanadas >= 12) {
            let ahorroPorDocena = (PRECIO_UNITARIO_REF * 12) - PRECIO_DOCENA_REF;
            desc = Math.floor(cantEmpanadas / 12) * ahorroPorDocena;
        }
        
        document.getElementById('contenedor-descuento').innerHTML = desc > 0 ? `<div class="descuento-info">Bonificación Docena: -$${desc.toLocaleString()}</div>` : '';
        document.getElementById('total-pedido').innerText = `Total: $${(totalBruto - desc).toLocaleString()}`;
    }

    async function syncDB(accion, prodId, precio) {
        const fd = new FormData();
        fd.append('mesa_id', MESA_ID); fd.append('tipo', TIPO); fd.append('origen', ORIGEN);
        fd.append('producto_id', prodId); fd.append('precio', precio); fd.append('accion', accion);
        const inputDir = document.getElementById('direccion_input');
        if (inputDir) fd.append('direccion', inputDir.value);
        if (ID_REFERENCIA > 0) fd.append('pedido_id', ID_REFERENCIA);
        
        const r = await fetch('../actions/actualizar_item.php', { method: 'POST', body: fd });
        const resText = (await r.text()).trim();
        if(resText === "vacio") { window.location.href = '../../index.php'; return; }
        if(!isNaN(resText) && ID_REFERENCIA === 0) ID_REFERENCIA = parseInt(resText);
    }

    function actualizarDireccion() { if (ID_REFERENCIA > 0) syncDB('update_dir', 0, 0); }

    function modificarCantidad(id, nombre, n) {
        let actual = items_raw.filter(i => i.nombre === nombre);
        let imp = actual.filter(i => i.impreso === 1).length;
        if (n < imp) { alert("Acción no permitida."); render(); return; }
        
        let diff = n - actual.length;
        if(diff > 0) { 
            for(let i=0; i<diff; i++) { 
                items_raw.push({id: id, nombre: nombre, precio: actual[0].precio, categoria: actual[0].categoria, impreso: 0}); 
                syncDB('add', id, actual[0].precio); 
            } 
        } else { 
            for(let i=0; i<Math.abs(diff); i++) { 
                let idx = items_raw.findLastIndex(i => i.nombre === nombre && i.impreso === 0); 
                if(idx!==-1){ items_raw.splice(idx,1); syncDB('remove', id, 0); } 
            } 
        }
        render();
    }

    function eliminarProductoCompleto(nombre) {
        let pends = items_raw.filter(i => i.nombre === nombre && i.impreso === 0);
        if(pends.length > 0 && confirm(`¿Remover items de ${nombre}?`)) {
            let id = pends[0].id;
            let c = pends.length;
            items_raw = items_raw.filter(i => !(i.nombre === nombre && i.impreso === 0));
            render();
            for(let i=0; i<c; i++) syncDB('remove', id, 0);
        }
    }

    async function imprimirComanda() {
        const pends = items_raw.filter(i => i.impreso === 0);
        if(pends.length === 0) return alert("Sin nuevos productos.");
        const obs = document.getElementById('observaciones').value.trim();
        const ag = {};
        pends.forEach(i => { if(!ag[i.nombre]) ag[i.nombre] = { nombre: i.nombre, cantidad: 0 }; ag[i.nombre].cantidad++; });
        
        const fd = new FormData();
        fd.append('destino', 'cocina'); fd.append('mesa_id', ID_REFERENCIA); fd.append('tipo', TIPO);
        fd.append('items', JSON.stringify(Object.values(ag))); fd.append('observaciones', obs);
        
        const resp = await fetch('../actions/imprimir_directo.php', { method: 'POST', body: fd });
        if((await resp.text()).trim() === "ok") {
            await fetch('../actions/marcar_impresos.php', { method: 'POST', body: fd });
            items_raw.forEach(i => i.impreso = 1);
            document.getElementById('observaciones').value = '';
            render();
        }
    }

    async function cerrarCaja() {
        if(ID_REFERENCIA === 0 || !confirm(`¿Finalizar pedido?`)) return;
        const fd = new FormData();
        fd.append('pedido_id', ID_REFERENCIA); fd.append('mesa_id', MESA_ID); fd.append('tipo', TIPO);
        const r = await fetch('../actions/finalizar_mesa.php', { method: 'POST', body: fd });
        if ((await r.text()).trim() === "ok") window.location.href = '../../index.php';
    }

    function cambiarTab(e, id) {
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById(id).style.display = 'block'; e.currentTarget.classList.add('active');
    }

    function filtrarPorSubcategoria(v, g) {
        const items = document.getElementById(g).getElementsByClassName('item-producto');
        for (let i = 0; i < items.length; i++) items[i].style.display = (v === 'todas' || items[i].classList.contains(v)) ? 'block' : 'none';
    }

    render();
</script>
</body>
</html>