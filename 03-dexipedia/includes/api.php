<?php
include_once __DIR__ . "/../config.php";


function h($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, "UTF-8");
}

// Pide una ruta a la PokeAPI como puede ser "pokemon/pikachu"
// y devuelve los datos como arreglo, o null si algo falla
function obtenerJson(string $ruta): ?array
{
    $url = POKEAPI_BASE . "/" . $ruta;

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_USERAGENT      => "Dexipedia/1.0 (proyecto escolar)",
    ]);

    $respuesta = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if ($respuesta === false || $codigo !== 200) {
        return null;
    }

    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : null;
}