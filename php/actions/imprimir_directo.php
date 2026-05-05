<?php
/**
 * SISTEMA AZTECA - CONTROLADOR DE IMPRESIÓN (COCINA CON OBSERVACIONES)
 */

spl_autoload_register(function ($class) {
    $prefix = 'Mike42\\';
    $base_dir = __DIR__ . '/lib/escpos-php/src/Mike42/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) { require_once $file; }
});

use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($str, $encoding = "UTF-8") {
        $str = mb_strtolower($str, $encoding);
        $firstChar = mb_substr($str, 0, 1, $encoding);
        $then = mb_substr($str, 1, null, $encoding);
        return mb_strtoupper($firstChar, $encoding) . $then;
    }
}

// 1. RECEPCIÓN DE DATOS
$destino       = $_POST['destino'] ?? 'cocina'; 
$mesa_id       = $_POST['mesa_id'] ?? 'N/A';
$tipo          = $_POST['tipo'] ?? 'Mesa';
$items         = json_decode($_POST['items'] ?? '[]', true);
$desc          = isset($_POST['descuento']) ? floatval($_POST['descuento']) : 0;
$total         = isset($_POST['total_final']) ? floatval($_POST['total_final']) : 0;

// NUEVO: Capturamos las observaciones enviadas desde el JS
$observaciones = isset($_POST['observaciones']) ? trim($_POST['observaciones']) : '';

// 2. PROCESAMIENTO DE ITEMS SEGÚN DESTINO
$items_procesados = [];
if (!empty($items)) {
    foreach ($items as $item) {
        $esParaCocina = isset($item['es_cocina']) ? (int)$item['es_cocina'] : 1;
        // Si vamos a cocina, ignoramos productos que no sean de cocina (bebidas, etc, según tu lógica)
        if ($destino === 'cocina' && $esParaCocina === 0) continue;
        
        $cat = !empty($item['categoria']) ? $item['categoria'] : 'Varios';
        $items_procesados[$cat][] = $item;
    }
}

// Si no hay nada que imprimir en cocina, cortamos ejecución
if (empty($items_procesados) && $destino === 'cocina') die("ok");

try {
    // 3. CONEXIÓN A IMPRESORA
    $nombreImpresora = ($destino === 'caja') ? "POS80-CC" : "COCINA"; 
    $connector = new WindowsPrintConnector($nombreImpresora);
    $printer = new Printer($connector);

    // 4. CABECERA DEL TICKET
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH | Printer::MODE_EMPHASIZED);
    $printer->text("AZTECA\n");
    $printer->selectPrintMode();
    $printer->text(strtoupper($tipo) . " #" . $mesa_id . "\n");
    $printer->text(date("d/m/Y H:i") . " hs\n");
    $printer->text("--------------------------------\n");

    // 5. CUERPO (PRODUCTOS)
    $printer->setJustification(Printer::JUSTIFY_LEFT);
    foreach ($items_procesados as $categoria => $productos) {
        $printer->setEmphasis(true);
        $printer->text("\n>> " . strtoupper($categoria) . "\n");
        $printer->setEmphasis(false);

        foreach ($productos as $p) {
            $nombreCorto = mb_substr($p['nombre'], 0, 24);
            
            if ($destino === 'cocina') {
                // Formato grande para cocina
                $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH | Printer::MODE_EMPHASIZED); 
                $printer->text($p['cantidad'] . " x " . strtoupper($nombreCorto) . "\n");
                $printer->selectPrintMode();
            } else {
                // Formato detallado para caja/cliente
                $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
                $printer->text(mb_ucfirst($nombreCorto) . "\n");
                $printer->selectPrintMode(); 
                $subtotal = floatval($p['total']);
                $unitario = ($p['cantidad'] > 0) ? ($subtotal / $p['cantidad']) : 0;
                $printer->text("   " . $p['cantidad'] . " x $" . number_format($unitario, 0, ',', '.') . " = $" . number_format($subtotal, 0, ',', '.') . "\n");
            }
        }
    }

    $printer->feed(1);
    $printer->setJustification(Printer::JUSTIFY_CENTER);
    $printer->text("--------------------------------\n");

    // 6. PIE DE TICKET Y LÓGICA DE OBSERVACIONES
    if ($destino === 'caja') {
        // TICKET DE CAJA: Mostramos totales, NO mostramos observaciones
        if ($desc > 0) {
            $printer->text("DESCUENTO PROMO: -$" . number_format($desc, 0, ',', '.') . "\n");
            $printer->text("--------------------------------\n");
        }
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH | Printer::MODE_EMPHASIZED);
        $printer->text("TOTAL: $" . number_format($total, 0, ',', '.') . "\n");
        $printer->selectPrintMode();
        $printer->feed(2);
        $printer->text("GRACIAS POR ELEGIRNOS\n");
    } else {
        // COMANDA DE COCINA: Mostramos observaciones si existen
        if (!empty($observaciones)) {
            $printer->feed(1);
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->setEmphasis(true);
            $printer->text("OBSERVACIONES:\n");
            $printer->setEmphasis(false);
            
            // Texto de observación en tamaño Doble Altura para legibilidad
            $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED);
            $printer->text(strtoupper($observaciones) . "\n");
            $printer->selectPrintMode();
            $printer->text("--------------------------------\n");
        }
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
        $printer->text("*** COMANDA DE COCINA ***\n");
    }

    // 7. FINALIZACIÓN
    $printer->feed(3);
    $printer->cut();
    $printer->close(); 
    echo "ok";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}