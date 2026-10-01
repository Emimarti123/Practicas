<?php require "productos.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro de pedidos</title>
</head>
<body>
    <h1>Registro de pedidos</h1>

    <form action="resultado.php" method="GET">
        <label for="cliente">Cliente:</label>
        <input type="text" id="cliente" name="cliente" required>
        <br><br>

        <table border="1">
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
            </tr>
            <?php foreach ($productos as $clave => $producto): ?>
            <tr>
                <td><?= $producto["nombre"] ?></td>
                <td>$<?= number_format($producto["precio"], 2) ?></td>
                <td>
                    <input type="number" name="cantidad[<?= $clave ?>]"
                           min="0" step="1" value="0">
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <br>

        <button type="submit">Calcular pedido</button>
    </form>
</body>
</html>