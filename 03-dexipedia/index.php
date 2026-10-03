<?php
include_once "config.php";
include_once "includes/api.php";
include_once "includes/pokedex.php";

// Reads and validates the filters that arrive by GET
$filters = [
    "q"     => mb_substr(trim((string) ($_GET["q"] ?? "")), 0, 30),
    "type"  => (string) ($_GET["type"] ?? ""),
    "gen"   => filter_var($_GET["gen"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 9]]),
    "stage" => (string) ($_GET["stage"] ?? ""),
    "sort"  => (string) ($_GET["sort"] ?? "number-asc"),
];
if (!isset($types[$filters["type"]])) {
    $filters["type"] = "";
}
if ($filters["gen"] === false) {
    $filters["gen"] = null;
}
if (!isset($stages[$filters["stage"]])) {
    $filters["stage"] = "";
}
if (!isset($sortOptions[$filters["sort"]])) {
    $filters["sort"] = "number-asc";
}

$page = filter_var($_GET["page"] ?? 1, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]) ?: 1;

$pokedex = loadPokedex();
$results = $pokedex ? filterPokedex($pokedex, $filters) : [];

$totalResults = count($results);
$totalPages = max(1, (int) ceil($totalResults / PER_PAGE));
$page = min($page, $totalPages);
$visible = array_slice($results, ($page - 1) * PER_PAGE, PER_PAGE);

$hasFilters = $filters["type"] !== "" || $filters["gen"] !== null || $filters["stage"] !== "";


function pageLink(array $filters, int $number): string
{
    $params = array_filter($filters + ["page" => $number], fn($value) => $value !== "" && $value !== null);
    return "index.php?" . http_build_query($params);
}

$pageTitle = "Dexipedia · Pokémon search";
include_once "includes/header.php";
?>

<section class="search">
    <form class="search-form" action="index.php" method="GET">
        <div class="search-box">
            <input type="search" name="q" value="<?= h($filters["q"]) ?>"
                   placeholder="Search by name or number" autocomplete="off" aria-label="Search Pokémon">
            <button type="submit">Search</button>
        </div>

        <div class="search-links">
            <a href="pokemon.php?id=<?= random_int(1, TOTAL_POKEMON) ?>">🎲 Surprise me</a>
        </div>

        <details class="filters" <?= $hasFilters ? "open" : "" ?>>
            <summary>Filters</summary>
            <div class="filters-grid">
                <label>Type
                    <select name="type">
                        <option value="">All</option>
                        <?php foreach ($types as $type => $color): ?>
                        <option value="<?= $type ?>" <?= $filters["type"] === $type ? "selected" : "" ?>><?= readable($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Generation
                    <select name="gen">
                        <option value="">All</option>
                        <?php foreach ($generations as $number => $generation): ?>
                        <option value="<?= $number ?>" <?= $filters["gen"] === $number ? "selected" : "" ?>><?= $generation ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Evolution stage
                    <select name="stage">
                        <option value="">All</option>
                        <?php foreach ($stages as $key => $stage): ?>
                        <option value="<?= $key ?>" <?= $filters["stage"] === $key ? "selected" : "" ?>><?= $stage ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>Sort by
                    <select name="sort">
                        <?php foreach ($sortOptions as $key => $option): ?>
                        <option value="<?= $key ?>" <?= $filters["sort"] === $key ? "selected" : "" ?>><?= $option ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <?php if ($hasFilters || $filters["q"] !== ""): ?>
            <a class="clear-link" href="index.php">Clear search</a>
            <?php endif; ?>
        </details>
    </form>
</section>

<?php if ($pokedex === null): ?>
    <p class="notice error">Could not load the Pokédex from the PokeAPI. Check your internet connection and reload the page.</p>
<?php elseif (!$visible): ?>
    <p class="notice">No Pokémon match your search.</p>
<?php else: ?>
    <p class="result-count"><?= $totalResults ?> Pokémon</p>

    <ul class="grid">
        <?php foreach ($visible as $pokemon): ?>
        <li>
            <a class="card" href="pokemon.php?id=<?= $pokemon["id"] ?>">
                <img src="<?= h(artworkUrl($pokemon["id"])) ?>" alt="<?= h($pokemon["name"]) ?>"
                     loading="lazy" width="160" height="160">
                <span class="card-number"><?= formatDexNumber($pokemon["id"]) ?></span>
                <span class="card-name"><?= h($pokemon["name"]) ?></span>
                <span class="type-list">
                    <?php foreach ($pokemon["types"] as $type): ?>
                    <span class="type" style="--type-color: <?= $types[$type] ?>"><?= readable($type) ?></span>
                    <?php endforeach; ?>
                </span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <?php if ($totalPages > 1): ?>
    <nav class="pagination" aria-label="Pages">
        <?php if ($page > 1): ?>
            <a href="<?= h(pageLink($filters, $page - 1)) ?>">&larr;</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <span class="current"><?= $i ?></span>
            <?php elseif ($i === 1 || $i === $totalPages || abs($i - $page) <= 2): ?>
                <a href="<?= h(pageLink($filters, $i)) ?>"><?= $i ?></a>
            <?php elseif (abs($i - $page) === 3): ?>
                <span class="dots">…</span>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="<?= h(pageLink($filters, $page + 1)) ?>">&rarr;</a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include_once "includes/footer.php"; ?>