<?php declare(strict_types=1);
require_once "parse_json.php";

function create_sql_connection()
{
    // Credentials
    if (!defined("SERVER_NAME")) {
        define("SERVER_NAME", "db");
    }
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

function safe_ingest_ohlcv(OHLCV $ohlcv, string $symbol): bool
{
    $conn = create_sql_connection();
    if (!is_a($conn, "PDO")) {
        return false;
    }
    
    // Use INSERT IGNORE to handle duplicates gracefully
    $sql = "INSERT IGNORE INTO daily_ohlcv (symbol, date, open, high, low, close, volume)
            VALUES (:symbol, :date, :open, :high, :low, :close, :volume)";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            'symbol' => $symbol,
            'date' => $ohlcv->date,
            'open' => $ohlcv->open,
            'high' => $ohlcv->high,
            'low' => $ohlcv->low,
            'close' => $ohlcv->close,
            'volume' => $ohlcv->volume
        ]);
        $conn = null;
        return true;
    } catch (PDOException $e) {
        $conn = null;
        return false;
    }
}

function ingest_ohlcv_array(array $ohlcv_array, string $symbol): int
{
    $ingested_count = 0;
    
    foreach ($ohlcv_array as $ohlcv) {
        if (safe_ingest_ohlcv($ohlcv, $symbol)) {
            $ingested_count++;
        }
    }
    
    return $ingested_count;
}

function fetch_ohlcv_data(string $symbol): ?array
{
    $conn = create_sql_connection();
    if (!is_a($conn, "PDO")) {
        return null;
    }
    
    $sql = "SELECT date, open, high, low, close, volume 
            FROM daily_ohlcv 
            WHERE symbol = :symbol 
            ORDER BY date ASC";
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute(['symbol' => $symbol]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $conn = null;
        return $results;
    } catch (PDOException $e) {
        $conn = null;
        return null;
    }
}

function fetch_from_tiingo(string $symbol): ?string
{
    // Make sure the API token is present as an environment variable
    if (!array_key_exists("TIINGO_API_TOKEN", $_ENV)) {
        return null;
    }
    
    $base_url = "https://api.tiingo.com/tiingo/daily/" . urlencode($symbol) . "/prices";
    
    // Build parameter string
    $parameters = [
        "token" => $_ENV["TIINGO_API_TOKEN"],
        "format" => "json",
    ];
    
    // Build final GET Request url
    $url = $base_url . "?" . http_build_query($parameters);
    
    // Create context with headers for the API request
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "Content-Type: application/json\r\n",
        ],
    ]);
    
    // Make the request
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }
    
    return $response;
}

?>
