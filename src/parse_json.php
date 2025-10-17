<?php declare(strict_types=1);

enum Timescale
{
    case Daily;
}

class Metadata
{
    public Timescale $type; // The type of data received, e.g. daily, weekly, hourly, etc.
    public $symbol;
    public $update_time;
    public $size;
    public $timezone;
}

class OHLCV
{
    public $open;
    public $high;
    public $low;
    public $close;
    public $volume;
}

function json_to_metadata_object(string $json_string): Metadata
{
    $array = json_decode($json_string, true);

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
?>
