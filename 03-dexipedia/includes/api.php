<?php

include_once __DIR__ . "/../config.php";


function h($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// Accepts "pokemon/25" or a full URL and returns the full URL
function apiUrl(string $path): string
{
    if (str_starts_with($path, "http")) {
        return $path;
    }
    $path = preg_replace("#^/?api/v2/#", "", $path);
    return API_BASE_URL . "/" . ltrim($path, "/");
}


function createRequest(string $path)
{
    $curl = curl_init(apiUrl($path));
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_USERAGENT      => "Dexipedia/1.0 (school project)",
    ]);
    return $curl;
}

// Requests ONE path from the PokeAPI (for example "pokemon/pikachu")
// and returns the data as an array, or null if something fails
function fetchJson(string $path): ?array
{
    $curl = createRequest($path);
    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false || $statusCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}
function fetchMany(array $paths): array
{
    $results = [];

    foreach (array_chunk($paths, 20, true) as $batch) {
        $multi = curl_multi_init();
        $requests = [];

        foreach ($batch as $key => $path) {
            $requests[$key] = createRequest($path);
            curl_multi_add_handle($multi, $requests[$key]);
        }

    
        do {
            curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi);
            }
        } while ($running);

        foreach ($requests as $key => $curl) {
            $data = null;
            if (curl_getinfo($curl, CURLINFO_HTTP_CODE) === 200) {
                $data = json_decode(curl_multi_getcontent($curl), true);
            }
            $results[$key] = is_array($data) ? $data : null;
            curl_multi_remove_handle($multi, $curl);
        }
        curl_multi_close($multi);
    }

    return $results;
}

// Finds the English text inside a list of translations from the API.
// Returns $fallback if there is no English entry.
function findEnglishText(array $entries, string $field, string $fallback = ""): string
{
    foreach ($entries as $entry) {
        if ($entry["language"]["name"] === "en") {
            return $entry[$field];
        }
    }
    return $fallback;
}

function idFromUrl(?string $url): ?int
{
    if ($url !== null && preg_match("#/(\d+)/?$#", $url, $match)) {
        return (int) $match[1];
    }
    return null;
}

function formatDexNumber(int $id): string
{
    return "#" . str_pad((string) $id, 4, "0", STR_PAD_LEFT);
}

// Turns "special-attack" into "Special Attack"
function readable(string $name): string
{
    return ucwords(str_replace("-", " ", $name));
}

// Official artwork of a Pokémon from its number
function artworkUrl(int $id): string
{
    return ARTWORK_URL . $id . ".png";
}