<?php declare(strict_types=1);
require "parse_json.php";

function create_sql_connection()
{
    // Credentials
    define("SERVER_NAME", "db");
    if (
        !array_key_exists("MARIADB_USER", $_ENV) ||
        !array_key_exists("MARIADB_PASSWORD", $_ENV) ||
        !array_key_exists("MARIADB_DATABASE", $_ENV)
    ) {
        return "Database credentials not found!";
    }

    try {
        // Create connection
        $conn = new PDO(
            "mysql:host=" .
                constant("SERVER_NAME") .
                ";dbname=" .
                $_ENV["MARIADB_DATABASE"],
            $_ENV["MARIADB_USER"],
            $_ENV["MARIADB_PASSWORD"],
        );
        // Set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        return $e->getMessage();
    }
}

function ingest_ohlcv(OHLCV $ohlcv, string $symbol)
{
    $conn = create_sql_connection();
    if (!is_a($conn, "PDO")) {
        die("Failed to create database connection! $conn");
    }
    // Prepare SQL query
    $sql = "INSERT INTO daily_ohlcv (symbol, date, open, high, low, close, volume)
    VALUES (\"$symbol\", \"$ohlcv->date\", $ohlcv->open, $ohlcv->high, $ohlcv->low, $ohlcv->close, $ohlcv->volume);";

    try {
        // Insert values into table
        $conn->exec($sql);
    } catch (PDOException $e) {
        die("SQL Query $sql failed!\n$e->getMessage()");
    }

    // Close the connection
    $conn = null;
    return 0;
}

?>
