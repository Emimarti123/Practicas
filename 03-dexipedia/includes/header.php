<?php include_once __DIR__ . "/api.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle ?? "Dexipedia") ?></title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <header class="top-bar">
        <a class="logo" href="index.php">
            <span class="logo-ball" aria-hidden="true"></span>
            Dexipedia
        </a>
    </header>
    <main class="container">