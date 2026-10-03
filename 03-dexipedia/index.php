<?php
include_once "config.php";
include_once "includes/api.php";

$pokemon = obtenerJson("pokemon/pikachu");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dexipedia</title>
</head>
<body>
    <h1>Dexipedia</h1>

    <?php if ($pokemon === null): ?>
        <p style="color:red;">No se pudo obtener la información de la PokeAPI.</p>
    <?php else: ?>
        <h2>#<?= $pokemon["id"] ?> <?= h(ucfirst($pokemon["name"])) ?></h2>

        <img src="<?= h($pokemon["sprites"]["other"]["official-artwork"]["front_default"]) ?>"
             alt="<?= h($pokemon["name"]) ?>" width="200">

        <p><strong>Altura:</strong> <?= $pokemon["height"] / 10 ?> m</p>
        <p><strong>Peso:</strong> <?= $pokemon["weight"] / 10 ?> kg</p>

        <p><strong>Tipos:</strong>
            <?php foreach ($pokemon["types"] as $tipo): ?>
                <?= h($tipo["type"]["name"]) ?>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>
</body>
</html>