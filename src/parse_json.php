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
Supports a json in this format:
{
    "Meta Data": {
        "1. Information": "Daily Prices (open, high, low, close) and Volumes",
        "2. Symbol": "AAPL",
        "3. Last Refreshed": "2025-10-17",
        "4. Output Size": "Compact",
        "5. Time Zone": "US/Eastern"
    }
}

Or without the enclosing "Meta Data" tag
*/
function json_to_metadata_object(string $json_string): Metadata
{
    $array = json_decode($json_string, true);
    $array = $array["Meta Data"];

    $metadata = new Metadata();
    try {
        $metadata->type = match ($array["1. Information"]) {
            "Daily Prices (open, high, low, close) and Volumes"
                => Timescale::Daily,
        };
    } catch (\UnhandledMatchError $e) {
        var_dump($e);
    }
    $metadata->symbol = $array["2. Symbol"];
    $metadata->update_time = $array["3. Last Refreshed"];
    $metadata->size = $array["4. Output Size"];
    $metadata->timezone = $array["5. Time Zone"];

    return $metadata;
}

/*
Supports a json in this format:
{
    "2025-10-17": {
        "1. open": "248.0200",
        "2. high": "253.3800",
        "3. low": "247.2700",
        "4. close": "252.2900",
        "5. volume": "49146961"
    }
}
*/
function json_to_ohlcv_object(string $json_string): OHLCV
{
    $array = json_decode($json_string, true);
    $date = array_key_first($array);
    $array = $array[$date];

    $ohlcv = new OHLCV();
    $ohlcv->date = $date;
    $ohlcv->open = $array["1. open"];
    $ohlcv->high = $array["2. high"];
    $ohlcv->low = $array["3. low"];
    $ohlcv->close = $array["4. close"];
    $ohlcv->volume = $array["5. volume"];

    return $ohlcv;
}

/*
Parses a full Alpha Vantage TIME_SERIES_DAILY response and returns an array of OHLCV objects.
Supports a json in this format:
{
    "Meta Data": {
        "1. Information": "Daily Prices (open, high, low, close) and Volumes",
        "2. Symbol": "AAPL",
        "3. Last Refreshed": "2025-10-17",
        "4. Output Size": "Compact",
        "5. Time Zone": "US/Eastern"
    },
    "Time Series (Daily)": {
        "2025-10-17": {
            "1. open": "248.0200",
            "2. high": "253.3800",
            "3. low": "247.2700",
            "4. close": "252.2900",
            "5. volume": "49146961"
        },
        "2025-10-16": {
            "1. open": "247.0200",
            "2. high": "252.3800",
            "3. low": "246.2700",
            "4. close": "251.2900",
            "5. volume": "49146962"
        }
    }
}
Returns null if the response contains an error or is invalid.
*/
function json_to_ohlcv_array(string $json_string): ?array
{
    $array = json_decode($json_string, true);
    
    // Check for API errors
    if (isset($array["Error Message"]) || isset($array["Note"])) {
        return null;
    }
    
    // Check if we have the time series data
    if (!isset($array["Time Series (Daily)"])) {
        return null;
    }
    
    $time_series = $array["Time Series (Daily)"];
    $ohlcv_array = [];
    
    // Parse each day's data
    foreach ($time_series as $date => $day_data) {
        $ohlcv = new OHLCV();
        $ohlcv->date = $date;
        $ohlcv->open = $day_data["1. open"];
        $ohlcv->high = $day_data["2. high"];
        $ohlcv->low = $day_data["3. low"];
        $ohlcv->close = $day_data["4. close"];
        $ohlcv->volume = $day_data["5. volume"];
        
        $ohlcv_array[] = $ohlcv;
    }
    
    return $ohlcv_array;
}
?>
