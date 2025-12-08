<?php declare(strict_types=1);

enum Timescale
{
    case Daily;
}

class Metadata
{
    public Timescale $type; // The type of data received, e.g. daily, weekly, hourly, etc.
    public string $ticker; // The stock ticker symbol
    public string $name; // Company name
    public string $description; // Company description
    public string $startDate; // Earliest available price date
    public string $endDate; // Latest available price date
    public string $exchangeCode; // Exchange code (e.g., NASDAQ, NYSE)
}

class OHLCV
{
    public $date;
    public $open;
    public $high;
    public $low;
    public $close;
    public $volume;
}

/*
Parses Tiingo meta endpoint JSON response.
Supports a json in this format:
{
    "ticker": "AAPL",
    "name": "Apple Inc",
    "description": "Apple Inc. (Apple) designs...",
    "startDate": "1980-12-12",
    "endDate": "2025-12-05",
    "exchangeCode": "NASDAQ"
}
*/
function json_to_metadata_object(string $json_string): Metadata
{
    $array = json_decode($json_string, true);
    
    if ($array === null || !isset($array["ticker"])) {
        throw new InvalidArgumentException("Invalid Tiingo meta JSON format");
    }

    $metadata = new Metadata();
    $metadata->type = Timescale::Daily; // Tiingo daily endpoint always returns daily data
    $metadata->ticker = $array["ticker"];
    $metadata->name = $array["name"] ?? "";
    $metadata->description = $array["description"] ?? "";
    $metadata->startDate = $array["startDate"] ?? "";
    $metadata->endDate = $array["endDate"] ?? "";
    $metadata->exchangeCode = $array["exchangeCode"] ?? "";

    return $metadata;
}

/*
Parses Tiingo daily endpoint JSON response (array format) and returns the first OHLCV object.
Supports a json in this format:
[{
    "date": "2025-10-17T00:00:00.000Z",
    "close": 252.29,
    "high": 253.38,
    "low": 247.27,
    "open": 248.02,
    "volume": 49146961,
    ...
}]
*/
function json_to_ohlcv_object(string $json_string): OHLCV
{
    $array = json_decode($json_string, true);
    
    if ($array === null || !is_array($array) || empty($array)) {
        throw new InvalidArgumentException("Invalid Tiingo daily JSON format or empty array");
    }
    
    // Get the first element
    $day_data = $array[0];
    
    // Extract date from ISO timestamp (2025-10-17T00:00:00.000Z -> 2025-10-17)
    $date_string = $day_data["date"];
    $date = substr($date_string, 0, 10); // Extract YYYY-MM-DD from ISO format

    $ohlcv = new OHLCV();
    $ohlcv->date = $date;
    $ohlcv->open = (string)$day_data["open"];
    $ohlcv->high = (string)$day_data["high"];
    $ohlcv->low = (string)$day_data["low"];
    $ohlcv->close = (string)$day_data["close"];
    $ohlcv->volume = (string)$day_data["volume"];

    return $ohlcv;
}

/*
Parses a full Tiingo daily endpoint response and returns an array of OHLCV objects.
Supports a json in this format (array of daily data objects):
[{
    "date": "2025-10-17T00:00:00.000Z",
    "close": 252.29,
    "high": 253.38,
    "low": 247.27,
    "open": 248.02,
    "volume": 49146961,
    ...
}, {
    "date": "2025-10-16T00:00:00.000Z",
    "close": 251.29,
    "high": 252.38,
    "low": 246.27,
    "open": 247.02,
    "volume": 49146962,
    ...
}]
Returns null if the response contains an error or is invalid.
Returns an empty array if the response is valid but contains no data.
*/
function json_to_ohlcv_array(string $json_string): ?array
{
    $array = json_decode($json_string, true);
    
    // Check if JSON decode failed
    if ($array === null) {
        return null;
    }
    
    // Check for Tiingo API errors (usually has a "detail" field)
    if (isset($array["detail"])) {
        return null;
    }
    
    // Check if we have an array (Tiingo returns an array directly)
    if (!is_array($array)) {
        return null;
    }
    
    // If it's an associative array (error object) without numeric keys, it's an error
    if (!empty($array) && !isset($array[0]) && !array_key_exists(0, $array)) {
        return null;
    }
    
    // Empty array is valid (no data available)
    if (empty($array)) {
        return [];
    }
    
    $ohlcv_array = [];
    
    // Parse each day's data
    foreach ($array as $day_data) {
        if (!isset($day_data["date"]) || !isset($day_data["open"])) {
            continue; // Skip invalid entries
        }
        
        // Extract date from ISO timestamp (2025-10-17T00:00:00.000Z -> 2025-10-17)
        $date_string = $day_data["date"];
        $date = substr($date_string, 0, 10); // Extract YYYY-MM-DD from ISO format
        
        $ohlcv = new OHLCV();
        $ohlcv->date = $date;
        $ohlcv->open = (string)$day_data["open"];
        $ohlcv->high = (string)$day_data["high"];
        $ohlcv->low = (string)$day_data["low"];
        $ohlcv->close = (string)$day_data["close"];
        $ohlcv->volume = (string)$day_data["volume"];
        
        $ohlcv_array[] = $ohlcv;
    }
    
    return $ohlcv_array;
}
?>
