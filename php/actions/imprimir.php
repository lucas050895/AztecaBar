<?php
/**
 * Ubicación: php/actions/imprimir.php
 * Este archivo gestiona la lógica de comunicación con las tiqueteras USB.
 */

// Importamos las clases necesarias de la librería Mike42
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

/**
 * Imprime las comandas divididas por destino (Cocina/Barra)
 */
function imprimirPedido($itemsComida, $itemsBebida, $nroMesa) {
    try {
        // --- 1. COMANDAS PARA COCINA (Comidas) ---
        if (!empty($itemsComida)) {
            // "TICKET_COCINA" es el nombre compartido en Windows
            $conectorC = new WindowsPrintConnector("TICKET_COCINA");
            $printerC = new Printer($conectorC);

            $printerC->setJustification(Printer::JUSTIFY_CENTER);
            $printerC->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $printerC->text("COCINA\n");
            $printerC->text("MESA " . $nroMesa . "\n");
            $printerC->selectPrintMode(); // Volver a tamaño normal
            $printerC->text(date("d/m/Y H:i:s") . "\n");
            $printerC->text("--------------------------------\n");

            $printerC->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($itemsComida as $item) {
                // Formato: [ ] 1 x Hamburguesa
                $printerC->text("[ ] " . $item['cantidad'] . " x " . $item['nombre'] . "\n");
            }

            $printerC->feed(3); // Espacio para cortar
            $printerC->cut();
            $printerC->close();
        }

        // --- 2. COMANDAS PARA BARRA (Bebidas) ---
        if (!empty($itemsBebida)) {
            // "TICKET_BARRA" es el nombre compartido en Windows
            $conectorB = new WindowsPrintConnector("TICKET_BARRA");
            $printerB = new Printer($conectorB);

            $printerB->setJustification(Printer::JUSTIFY_CENTER);
            $printerB->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_DOUBLE_WIDTH);
            $printerB->text("BARRA\n");
            $printerB->text("MESA " . $nroMesa . "\n");
            $printerB->selectPrintMode();
            $printerB->text(date("d/m/Y H:i:s") . "\n");
            $printerB->text("--------------------------------\n");

            $printerB->setJustification(Printer::JUSTIFY_LEFT);
            foreach ($itemsBebida as $item) {
                // Formato: ( ) 1 x Cerveza
                $printerB->text("( ) " . $item['cantidad'] . " x " . $item['nombre'] . "\n");
            }

            $printerB->feed(3);
            $printerB->cut();
            $printerB->close();
        }

    } catch (Exception $e) {
        // Log de error si la impresora no responde
        error_log("Error de impresión: " . $e->getMessage());
    }
}

/**
 * Imprime el ticket de precuenta para el cliente
 */
function imprimirCuentaFinal($itemsTodo, $total, $nroMesa) {
    try {
        // La cuenta siempre se imprime en la Barra (Caja)
        $conector = new WindowsPrintConnector("TICKET_BARRA");
        $printer = new Printer($conector);

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH);
        $printer->text("BAR AZTECA\n");
        $printer->selectPrintMode();
        $printer->text("Mesa: " . $nroMesa . "\n");
        $printer->text("Fecha: " . date("d/m/Y H:i") . "\n");
        $printer->text("--------------------------------\n");

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        foreach ($itemsTodo as $item) {
            // Nombre del producto
            $printer->text($item['nombre'] . "\n");
            // Precio alineado a la derecha (asumiendo papel de 80mm)
            $printer->text("   1 x $" . number_format($item['precio'], 2) . "\n");
        }

        $printer->text("--------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_RIGHT);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_HEIGHT);
        $printer->text("TOTAL: $" . number_format($total, 2) . "\n");
        $printer->selectPrintMode();
        
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(1);
        $printer->text("Gracias por su visita\n");
        
        $printer->feed(3);
        $printer->cut();
        $printer->close();

    } catch (Exception $e) {
        error_log("Error al imprimir cuenta: " . $e->getMessage());
    }
}