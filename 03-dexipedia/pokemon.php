<?php
include_once "config.php";
include_once "includes/api.php";
include_once "includes/pokedex.php";

// Validates that the id is a number between 1 and 1025
$id = filter_var($_GET["id"] ?? "", FILTER_VALIDATE_INT, [
    "options" => ["min_range" => 1, "max_range" => TOTAL_POKEMON]
]);

$error = "";

if ($id === false) {
    http_response_code(404);
    $error = "Invalid Pokémon number. It must be between 1 and " . TOTAL_POKEMON . ".";
} else {
    // Both requests are made at the same time
    $data = fetchMany(["pokemon" => "pokemon/$id", "species" => "pokemon-species/$id"]);
    $pokemon = $data["pokemon"];
    $species = $data["species"];

    if ($pokemon === null || $species === null) {
        $error = "Could not get the data from the PokeAPI.";
    }
}


function evolutionCondition(array $details): string
{
    if (!$details) {
        return "";
    }
    $detail = $details[0];
    $parts = [];

    if (($detail["trigger"]["name"] ?? "") === "trade") $parts[] = "Trade";
    if (!empty($detail["item"]["name"])) $parts[] = "Use " . readable($detail["item"]["name"]);
    if (!empty($detail["min_level"])) $parts[] = "Lv. " . $detail["min_level"];
    if (!empty($detail["min_happiness"])) $parts[] = "High friendship";
    if (!empty($detail["held_item"]["name"])) $parts[] = "Holding " . readable($detail["held_item"]["name"]);
    if (!empty($detail["known_move"]["name"])) $parts[] = "Knowing " . readable($detail["known_move"]["name"]);
    if (($detail["time_of_day"] ?? "") === "day") $parts[] = "Daytime";
    if (($detail["time_of_day"] ?? "") === "night") $parts[] = "Nighttime";

    return $parts ? implode(" · ", $parts) : "Special condition";
}


// [0 => [Charmander], 1 => [Charmeleon], 2 => [Charizard]]
function chainStages(array $node, array $names, int $level = 0, array &$stages = []): array
{
    $stageId = idFromUrl($node["species"]["url"]);
    if ($stageId <= TOTAL_POKEMON) {
        $stages[$level][] = [
            "id"        => $stageId,
            "name"      => $names[$stageId] ?? readable($node["species"]["name"]),
            "condition" => evolutionCondition($node["evolution_details"]),
        ];
    }
    foreach ($node["evolves_to"] as $next) {
        chainStages($next, $names, $level + 1, $stages);
    }
    return $stages;
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

    $pokemonTypes = array_map(fn($type) => $type["type"]["name"], $pokemon["types"]);
    $mainColor = $types[$pokemonTypes[0]] ?? "#cc0000";
    $image = $pokemon["sprites"]["other"]["official-artwork"]["front_default"] ?? artworkUrl($id);

    $paths = [];
    foreach ($pokemonTypes as $type) {
        $paths[$type] = "type/$type";
    }
    $paths["chain"] = $species["evolution_chain"]["url"];
    $extra = fetchMany($paths);

    $multipliers = array_fill_keys(array_keys($types), 1);
    foreach ($pokemonTypes as $type) {
        $relations = $extra[$type]["damage_relations"] ?? null;
        if ($relations === null) {
            continue;
        }
        foreach (["double_damage_from" => 2, "half_damage_from" => 0.5, "no_damage_from" => 0] as $field => $factor) {
            foreach ($relations[$field] as $attacker) {
                if (isset($multipliers[$attacker["name"]])) {
                    $multipliers[$attacker["name"]] *= $factor;
                }
            }
        }
    }
    $weaknesses = array_filter($multipliers, fn($m) => $m > 1);
    $resistances = array_filter($multipliers, fn($m) => $m > 0 && $m < 1);
    $immunities = array_filter($multipliers, fn($m) => $m == 0);
    arsort($weaknesses);
    asort($resistances);

    $evolutions = $extra["chain"] ? chainStages($extra["chain"]["chain"], savedNames()) : [];

    $gender = $species["gender_rate"] < 0
        ? "Genderless"
        : (100 - $species["gender_rate"] * 12.5) . "% ♂ · " . ($species["gender_rate"] * 12.5) . "% ♀";
}

$backLink = "index.php";
$previousPage = parse_url($_SERVER["HTTP_REFERER"] ?? "");
if (str_ends_with($previousPage["path"] ?? "", "index.php") && !empty($previousPage["query"])) {
    $backLink .= "?" . $previousPage["query"];
}

$pageTitle = $error ? "Pokémon not found · Dexipedia" : "$name · Dexipedia";
include_once "includes/header.php";
?>

<a class="back-link" href="<?= h($backLink) ?>">&larr; Back to search</a>

<?php if ($error): ?>
    <p class="notice error"><?= h($error) ?></p>
<?php else: ?>

<article class="detail" style="--main-color: <?= $mainColor ?>">
    <div class="detail-header">
        <img class="detail-image" src="<?= h($image) ?>" alt="<?= h($name) ?>" width="320" height="320">
        <div>
            <span class="detail-number"><?= formatDexNumber($id) ?></span>
            <h1><?= h($name) ?></h1>
            <p class="detail-genus"><?= h($genus) ?></p>
            <div class="type-list">
                <?php foreach ($pokemonTypes as $type): ?>
                <a class="type" style="--type-color: <?= $types[$type] ?>" href="index.php?type=<?= $type ?>"><?= readable($type) ?></a>
                <?php endforeach; ?>
            </div>
            <p class="detail-description"><?= h($description) ?></p>
        </div>
    </div>

    <div class="panels">
        <section class="panel">
            <h2>Data</h2>
            <dl class="data-list">
                <dt>Height</dt><dd><?= number_format($pokemon["height"] / 10, 1) ?> m</dd>
                <dt>Weight</dt><dd><?= number_format($pokemon["weight"] / 10, 1) ?> kg</dd>
                <dt>Generation</dt><dd><?= $generations[idFromUrl($species["generation"]["url"])] ?? "" ?></dd>
                <dt>Gender</dt><dd><?= $gender ?></dd>
                <dt>Capture rate</dt><dd><?= $species["capture_rate"] ?></dd>
                <dt>Abilities</dt>
                <dd>
                    <?php foreach ($pokemon["abilities"] as $ability): ?>
                    <span class="ability"><?= h(readable($ability["ability"]["name"])) ?><?= $ability["is_hidden"] ? " <small>(hidden)</small>" : "" ?></span>
                    <?php endforeach; ?>
                </dd>
            </dl>
        </section>

        <section class="panel">
            <h2>Base stats</h2>
            <table class="stats">
                <?php $total = 0; ?>
                <?php foreach ($pokemon["stats"] as $stat): ?>
                <?php $total += $stat["base_stat"]; ?>
                <tr>
                    <th><?= $stat["stat"]["name"] === "hp" ? "HP" : readable($stat["stat"]["name"]) ?></th>
                    <td class="stat-value"><?= $stat["base_stat"] ?></td>
                    <td><div class="stat-bar"><span style="width: <?= min(100, round($stat["base_stat"] / 255 * 100)) ?>%"></span></div></td>
                </tr>
                <?php endforeach; ?>
                <tr class="stat-total">
                    <th>Total</th>
                    <td class="stat-value"><?= $total ?></td>
                    <td></td>
                </tr>
            </table>
        </section>

        <section class="panel">
            <h2>Type effectiveness</h2>
            <?php foreach (["Weak to" => $weaknesses, "Resistant to" => $resistances, "Immune to" => $immunities] as $title => $group): ?>
                <?php if ($group): ?>
                <h3><?= $title ?></h3>
                <div class="type-list">
                    <?php foreach ($group as $type => $multiplier): ?>
                    <span class="type" style="--type-color: <?= $types[$type] ?>">
                        <?= readable($type) ?> ×<?= $multiplier == 0.25 ? "¼" : ($multiplier == 0.5 ? "½" : $multiplier) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>
    </div>

    <section class="panel">
        <h2>Evolution chain</h2>
        <?php if (count($evolutions) <= 1): ?>
            <p><?= h($name) ?> does not evolve.</p>
        <?php else: ?>
        <div class="evolution-chain">
            <?php foreach ($evolutions as $level => $members): ?>
                <?php if ($level > 0): ?><span class="arrow">&rarr;</span><?php endif; ?>
                <div class="evolution-stage">
                    <?php foreach ($members as $member): ?>
                    <a class="evolution <?= $member["id"] === $id ? "current" : "" ?>" href="pokemon.php?id=<?= $member["id"] ?>">
                        <img src="<?= h(artworkUrl($member["id"])) ?>" alt="" loading="lazy" width="96" height="96">
                        <strong><?= h($member["name"]) ?></strong>
                        <small><?= formatDexNumber($member["id"]) ?></small>
                        <?php if ($member["condition"]): ?><small><?= h($member["condition"]) ?></small><?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</article>

<nav class="detail-nav">
    <?php if ($id > 1): ?>
        <a href="pokemon.php?id=<?= $id - 1 ?>">&larr; <?= formatDexNumber($id - 1) ?></a>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
    <?php if ($id < TOTAL_POKEMON): ?>
        <a href="pokemon.php?id=<?= $id + 1 ?>"><?= formatDexNumber($id + 1) ?> &rarr;</a>
    <?php endif; ?>
</nav>

<?php endif; ?>

<?php include_once "includes/footer.php"; ?>