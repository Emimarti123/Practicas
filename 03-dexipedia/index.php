<?php
include_once "config.php";
include_once "includes/api.php";

$pokemon = fetchJson("pokemon/pikachu");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dexipedia</title>
</head>
<body>
    <h1>Dexipedia</h1>

    <?php if ($pokemon === null): ?>
        <p style="color:red;">Could not get the data from the PokeAPI.</p>
    <?php else: ?>
        <h2>
            <a href="pokemon.php?id=<?= $pokemon["id"] ?>">
                <?= formatDexNumber($pokemon["id"]) ?> <?= h(readable($pokemon["name"])) ?>
            </a>
        </h2>

        <img src="<?= h($pokemon["sprites"]["other"]["official-artwork"]["front_default"]) ?>"
             alt="<?= h($pokemon["name"]) ?>" width="200">

        <p><strong>Height:</strong> <?= number_format($pokemon["height"] / 10, 1) ?> m</p>
        <p><strong>Weight:</strong> <?= number_format($pokemon["weight"] / 10, 1) ?> kg</p>

        <p><strong>Types:</strong>
            <?php foreach ($pokemon["types"] as $type): ?>
                <?= h(readable($type["type"]["name"])) ?>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>
</body>
</html>