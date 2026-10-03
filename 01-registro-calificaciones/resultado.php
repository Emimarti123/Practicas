
    <?php
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $nombre = trim((string) ($_GET["nombre"] ?? ""));
        $materia = trim((string) ($_GET["materia"] ?? ""));
        $calificacion = filter_var(
            $_GET["calificacion"] ?? "",
            FILTER_VALIDATE_FLOAT
        );

        if (
            $nombre === "" ||
            $materia === "" ||
            $calificacion === false ||
            $calificacion < 0 ||
            $calificacion > 10
        ) {
            echo "<p>Ingresa todos los datos y una calificación entre 0 y 10.</p>";
        } else {
            echo "<h2>Datos registrados</h2>";
            echo "<p>Nombre: " . htmlspecialchars($nombre, ENT_QUOTES, "UTF-8") . "</p>";
            echo "<p>Materia: " . htmlspecialchars($materia, ENT_QUOTES, "UTF-8") . "</p>";
            echo "<p>Calificación: " . $calificacion . "</p>";
        }
    }
    ?>
