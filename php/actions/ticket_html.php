<?php
function generarTicketHTML($items, $mesa, $tipo) {
    echo "
    <div class='ticket-print' id='ticket-to-print'>
        <h2>BAR AZTECA - $tipo</h2>
        <p>Mesa: $mesa | Fecha: " . date('d/m/Y H:i') . "</p>
        <hr>
        <table>";
    foreach ($items as $item) {
        echo "<tr>
                <td>{$item['cantidad']} x {$item['nombre']}</td>
              </tr>";
    }
    echo "</table>
        <hr>
        <p>LISTO PARA PREPARAR</p>
    </div>";
}