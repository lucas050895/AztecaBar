function actualizarMesas() {
    fetch('php/actions/check_status.php')
        .then(response => response.json())
        .then(data => {
            data.forEach(mesa => {
                let elementoMesa = document.getElementById(`mesa-${mesa.id_mesa}`);
                if (elementoMesa) {
                    // Si el estado cambió, actualizamos la clase CSS o el color
                    if (mesa.estado === 'ocupada') {
                        elementoMesa.classList.add('mesa-ocupada');
                    } else {
                        elementoMesa.classList.remove('mesa-ocupada');
                    }
                }
            });
        });
}

// Ejecutar cada 3 segundos
setInterval(actualizarMesas, 3000);