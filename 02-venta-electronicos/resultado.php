<?php
require "productos.php";

$limiteDescuento = 500;
$porcentajeDescuento = 0.10;

$cliente = trim((string) ($_GET["cliente"] ?? ""));
$cantidades = $_GET["cantidad"] ?? [];
if (!is_array($cantidades)) {
    $cantidades = [];
}

$errores = [];
$lineas = [];
$subtotal = 0;

if ($cliente === "") {
    $errores[] = "El nombre del cliente es obligatorio.";
}

foreach ($productos as $clave => $producto) {
    $valor = trim((string) ($cantidades[$clave] ?? "0"));
    if ($valor === "") {
        $valor = "0";
    }

    $cantidad = filter_var($valor, FILTER_VALIDATE_INT, [
        "options" => ["min_range" => 0]
    ]);

    if ($cantidad === false) {
        $errores[] = "La cantidad de " . $producto["nombre"] . " no es válida.";
        continue;
    }

    if ($cantidad > 0) {
        $importe = $producto["precio"] * $cantidad;
        $subtotal += $importe;
        $lineas[] = [
            "nombre"   => $producto["nombre"],
            "precio"   => $producto["precio"],
            "cantidad" => $cantidad,
            "importe"  => $importe,
        ];
    }
}

if (!$errores && !$lineas) {
    $errores[] = "Agrega al menos un producto al pedido.";
}

$descuento = $subtotal > $limiteDescuento ? $subtotal * $porcentajeDescuento : 0;
$total = $subtotal - $descuento;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen del pedido</title>
</head>
<body>
    <h1>Resumen del pedido</h1>

    <?php if ($errores): ?>
        <?php foreach ($errores as $error): ?>
            <p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
        <?php endforeach; ?>
    <?php else: ?>
        <p><strong>Cliente:</strong> <?= htmlspecialchars($cliente, ENT_QUOTES, "UTF-8") ?></p>

        <table border="1">
            <tr>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Importe</th>
            </tr>
            <?php foreach ($lineas as $linea): ?>
            <tr>
                <td><?= $linea["nombre"] ?></td>
                <td>$<?= number_format($linea["precio"], 2) ?></td>
                <td><?= $linea["cantidad"] ?></td>
                <td>$<?= number_format($linea["importe"], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?> MXN</p>
        <?php if ($descuento > 0): ?>
            <p><strong>Descuento (10%):</strong> -$<?= number_format($descuento, 2) ?> MXN</p>
        <?php else: ?>
            <p>Sin descuento (aplica en compras mayores a $<?= number_format($limiteDescuento, 2) ?>).</p>
        <?php endif; ?>
        <p><strong>Total:</strong> $<?= number_format($total, 2) ?> MXN</p>
    <?php endif; ?>

    <br>
    <a href="index.php">Nuevo pedido</a>
</body>
</html>