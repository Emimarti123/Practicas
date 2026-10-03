<?php
// Builds, saves and filters the list of all 1025 Pokémon used by the search
include_once __DIR__ . "/api.php";
include_once __DIR__ . "/types.php";

$generations = [
    1 => "I · Kanto",
    2 => "II · Johto",
    3 => "III · Hoenn",
    4 => "IV · Sinnoh",
    5 => "V · Unova",
    6 => "VI · Kalos",
    7 => "VII · Alola",
    8 => "VIII · Galar",
    9 => "IX · Paldea",
];

$stages = [
    "basic"  => "Basic",
    "stage1" => "Stage 1",
    "stage2" => "Stage 2",
    "none"   => "Doesn't evolve",
];

$sortOptions = [
    "number-asc"  => "Lowest number",
    "number-desc" => "Highest number",
    "name-asc"    => "Name A-Z",
    "name-desc"   => "Name Z-A",
];

function loadPokedex(): ?array
{
    if (is_file(CACHE_FILE)) {
        $pokedex = json_decode(file_get_contents(CACHE_FILE), true);
        if (is_array($pokedex) && count($pokedex) === TOTAL_POKEMON) {
            return $pokedex;
        }
    }

    $pokedex = buildPokedex();
    if ($pokedex !== null) {
        if (!is_dir(dirname(CACHE_FILE))) {
            mkdir(dirname(CACHE_FILE), 0775, true);
        }
        file_put_contents(CACHE_FILE, json_encode($pokedex, JSON_UNESCAPED_UNICODE));
    }
    return $pokedex;
}

// Names of every Pokémon from the saved list 
function savedNames(): array
{
    $names = [];
    if (is_file(CACHE_FILE)) {
        foreach (json_decode(file_get_contents(CACHE_FILE), true) ?: [] as $pokemon) {
            $names[$pokemon["id"]] = $pokemon["name"];
        }
    }
    return $names;
}

// Downloads the data of every Pokémon 
function buildPokedex(): ?array
{
    global $types;

   
    set_time_limit(0);

    // 1. Species: English name, generation and what it evolves from
    $paths = [];
    for ($id = 1; $id <= TOTAL_POKEMON; $id++) {
        $paths[$id] = "pokemon-species/$id";
    }
    $species = fetchMany($paths);
    if (in_array(null, $species, true)) {
        return null;
    }

    // 2. Types: each type returns the list of Pokémon that have it
    $typePaths = [];
    foreach (array_keys($types) as $type) {
        $typePaths[$type] = "type/$type";
    }
    $pokemonTypes = [];
    foreach (fetchMany($typePaths) as $type => $data) {
        if ($data === null) {
            return null;
        }
        foreach ($data["pokemon"] as $entry) {
            $id = idFromUrl($entry["pokemon"]["url"]);
            if ($id <= TOTAL_POKEMON) {
                $pokemonTypes[$id][$entry["slot"]] = $type;
            }
        }
    }

    // 3. How many species share each evolution chain
    $chainSizes = [];
    foreach ($species as $data) {
        $chain = idFromUrl($data["evolution_chain"]["url"] ?? null);
        $chainSizes[$chain] = ($chainSizes[$chain] ?? 0) + 1;
    }

    // 4. One record per Pokémon with only what the search needs
    $pokedex = [];
    foreach ($species as $id => $data) {
        

        $stage = 0;
        $previous = idFromUrl($data["evolves_from_species"]["url"] ?? null);
        while ($previous !== null) {
            if (!$species[$previous]["is_baby"]) {
                $stage++;
            }
            $previous = idFromUrl($species[$previous]["evolves_from_species"]["url"] ?? null);
        }

        $typesOfPokemon = $pokemonTypes[$id] ?? [];
        ksort($typesOfPokemon);

        $chain = idFromUrl($data["evolution_chain"]["url"] ?? null);

        $pokedex[] = [
            "id"         => $id,
            "slug"       => $data["name"],
            "name"       => findEnglishText($data["names"], "name", readable($data["name"])),
            "types"      => array_values($typesOfPokemon),
            "generation" => idFromUrl($data["generation"]["url"]),
            "stage"      => min($stage, 2),
            "evolves"    => ($chainSizes[$chain] ?? 1) > 1,
        ];
    }

    return $pokedex;
}

// Lowercase and without accents, so "flabebe" finds "Flabébé"
function normalize(string $text): string
{
    $text = mb_strtolower($text, "UTF-8");
    return strtr($text, ["á" => "a", "é" => "e", "í" => "i", "ó" => "o", "ú" => "u"]);
}


function filterPokedex(array $pokedex, array $filters): array
{
    $search = normalize($filters["q"]);

   
    $number = null;
    if (preg_match("/^#?0*(\d+)$/", $search, $match)) {
        $number = (int) $match[1];
    }

    $results = array_filter($pokedex, function ($pokemon) use ($filters, $search, $number) {
        if ($search !== "") {
            if ($number !== null && $pokemon["id"] !== $number) {
                return false;
            }
            if ($number === null && !str_contains(normalize($pokemon["name"]), $search)) {
                return false;
            }
        }
        if ($filters["type"] !== "" && !in_array($filters["type"], $pokemon["types"], true)) {
            return false;
        }
        if ($filters["gen"] !== null && $pokemon["generation"] !== $filters["gen"]) {
            return false;
        }
        return match ($filters["stage"]) {
            "basic"  => $pokemon["evolves"] && $pokemon["stage"] === 0,
            "stage1" => $pokemon["evolves"] && $pokemon["stage"] === 1,
            "stage2" => $pokemon["evolves"] && $pokemon["stage"] === 2,
            "none"   => !$pokemon["evolves"],
            default  => true,
        };
    });

    usort($results, function ($a, $b) use ($filters) {
        return match ($filters["sort"]) {
            "number-desc" => $b["id"] <=> $a["id"],
            "name-asc"    => strcmp(normalize($a["name"]), normalize($b["name"])),
            "name-desc"   => strcmp(normalize($b["name"]), normalize($a["name"])),
            default       => $a["id"] <=> $b["id"],
        };
    });

    return $results;
}