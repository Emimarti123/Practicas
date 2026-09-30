<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de calificaciones</title>
</head>
<body>
    <h1>Registro de calificaciones</h1>

    <form method="POST">
        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" required>
        <br><br>

        <label for="materia">Materia:</label>
        <input type="text" id="materia" name="materia" required>
        <br><br>

        <label for="calificacion">Calificación:</label>
        <input type="number" id="calificacion" name="calificacion"
               min="0" max="10" step="0.1" required>
        <br><br>

        <button type="submit">Enviar</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $nombre = trim((string) ($_POST["nombre"] ?? ""));
        $materia = trim((string) ($_POST["materia"] ?? ""));
        $calificacion = filter_var(
            $_POST["calificacion"] ?? "",
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
</body>
</html>