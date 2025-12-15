<?php

declare(strict_types=1);

// TODO: This page should use a Database class and check its status via a class method

$serverName = "db";
$databaseName = "root_db";

// Test database
$pdoOpts = [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];
$serverUri = "mysql:host={$serverName};dbname={$databaseName};charset=utf8mb4";

echo "Connecting to: <a href=\"\">{$serverUri}</a><br />";

try {
    $pdo = new PDO(
        $serverUri,
        $_ENV["MARIADB_USER"],
        $_ENV["MARIADB_PASSWORD"],
        $pdoOpts,
    );
} catch (Exception $ex) {
    echo "Could not connect to the database: " . $ex->getMessage() . "<br />";
    error_log($ex->getMessage());
    die();
}

echo "Database OK<br />";

try {
    $dbVersion = $pdo->query("SELECT VERSION()")->fetch()["VERSION()"];
} catch (Exception $ex) {
    echo "Could not connect to the database version info: "
        . $ex->getMessage()
        . "<br />";
    error_log($ex->getMessage());
    die();
}

echo "Detected database: {$dbVersion}<br />";
