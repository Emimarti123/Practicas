<?php
// Se muestra en login y registro cuando el navegador ya tiene una sesión abierta,
// en lugar de mandar al usuario a su panel sin explicación.
// Requiere $sesionActiva (de usuarioEnSesion()) y $paginaActual.
?>
<section class="tarjeta tarjeta-formulario">
    <h1>Ya hay una sesión abierta</h1>
    <p>
        En este navegador está abierta la sesión de
        <strong><?php echo htmlspecialchars($sesionActiva["nombre"]); ?></strong>
        (<?php echo nombreRol($sesionActiva["rol"]); ?>).
        Para entrar o registrar otra cuenta, primero ciérrala.
    </p>
    <div class="acciones">
        <a href="<?php echo panelDeRol($sesionActiva["rol"]); ?>" class="boton">Ir a mi panel</a>
        <a href="logout.php?volver=<?php echo $paginaActual; ?>" class="boton boton-claro">Cerrar sesión y continuar</a>
    </div>
    <p class="texto-secundario texto-chico">
        Todas las pestañas de un navegador comparten la misma sesión.
        Para usar varias cuentas a la vez, abre cada una en otro navegador o en una ventana de incógnito.
    </p>
</section>
