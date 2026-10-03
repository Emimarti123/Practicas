<?php
include_once "config.php";
include_once "includes/api.php";

// Validates that the id is a number between 1 and 1025
$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1, "max_range" => TOTAL_POKEMON]
]);

$error = "";
$pokemon = null;
$species = null;

if ($id === false) {
    $error = "Invalid Pokémon number. It must be between 1 and " . TOTAL_POKEMON . ".";
} else {
    $pokemon = fetchJson("pokemon/$id");
    $species = fetchJson("pokemon-species/$id");

    if ($pokemon === null || $species === null) {
        $error = "Could not get the data from the PokeAPI.";
    }
}

if (!$error) {
    $name = findEnglishText($species["names"], "name", readable($pokemon["name"]));
    $genus = findEnglishText($species["genera"], "genus");

    $description = "";
    foreach (array_reverse($species["flavor_text_entries"]) as $entry) {
        if ($entry["language"]["name"] === "en") {
            $description = preg_replace("/\s+/u", " ", $entry["flavor_text"]);
            break;
        }
    }

    $image = $pokemon["sprites"]["other"]["official-artwork"]["front_default"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $error ? "Pokémon not found" : h($name) ?> · Dexipedia</title>
</head>
<body>
    <a href="index.php">&larr; Back</a>

    <?php if ($error): ?>
        <p style="color:red;"><?= h($error) ?></p>
    <?php else: ?>
        <h1><?= formatDexNumber($id) ?> <?= h($name) ?></h1>
        <p><em><?= h($genus) ?></em></p>

        <img src="<?= h($image) ?>" alt="<?= h($name) ?>" width="250">

        <p><?= h($description) ?></p>

        <h2>Data</h2>
        <p><strong>Height:</strong> <?= number_format($pokemon["height"] / 10, 1) ?> m</p>
        <p><strong>Weight:</strong> <?= number_format($pokemon["weight"] / 10, 1) ?> kg</p>
        <p><strong>Types:</strong>
            <?php foreach ($pokemon["types"] as $type): ?>
                <?= h(readable($type["type"]["name"])) ?>
            <?php endforeach; ?>
        </p>
        <p><strong>Abilities:</strong>
            <?php foreach ($pokemon["abilities"] as $ability): ?>
                <?= h(readable($ability["ability"]["name"])) ?><?= $ability["is_hidden"] ? " (hidden)" : "" ?>
            <?php endforeach; ?>
        </p>

        <h2>Base stats</h2>
        <table border="1">
            <?php foreach ($pokemon["stats"] as $stat): ?>
            <tr>
                <th><?= h(readable($stat["stat"]["name"])) ?></th>
                <td><?= $stat["base_stat"] ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <p>
            <?php if ($id > 1): ?>
                <a href="pokemon.php?id=<?= $id - 1 ?>">&larr; Previous</a>
            <?php endif; ?>
            <?php if ($id < TOTAL_POKEMON): ?>
                <a href="pokemon.php?id=<?= $id + 1 ?>">Next &rarr;</a>
            <?php endif; ?>
        </p>
    <?php endif; ?>
</body>
</html>