<?php
include_once __DIR__ . "/../config.php";


function h($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, "UTF-8");
}

// Requests a path from the PokeAPI (for example "pokemon/pikachu")
// and returns the data as an array, or null if something fails
function fetchJson(string $path): ?array
{
    $url = API_BASE_URL . "/" . $path;

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => "Dexipedia/1.0 (school project)",
    ]);

    $response = curl_exec($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($response === false || $statusCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function findEnglishText(array $entries, string $field, string $fallback = ""): string
{
    foreach ($entries as $entry) {
        if ($entry["language"]["name"] === "en") {
            return $entry[$field];
        }
    }
    return $fallback;
}

function formatDexNumber(int $id): string
{
    return "#" . str_pad((string) $id, 4, "0", STR_PAD_LEFT);
}

function readable(string $name): string
{
    return ucwords(str_replace("-", " ", $name));
}