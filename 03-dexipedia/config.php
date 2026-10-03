<?php
// Dexipedia general settings
define("API_BASE_URL", "https://pokeapi.co/api/v2");

// Total Pokémon in the National Pokédex (generations 1 to 9)
define("TOTAL_POKEMON", 1025);

// Pokémon shown per page in the search results
define("PER_PAGE", 48);

define("CACHE_FILE", __DIR__ . "/cache/pokedex.json");

// Official artwork of each Pokémon 
define("ARTWORK_URL", "https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/other/official-artwork/");