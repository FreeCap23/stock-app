<?php
use PHPUnit\Framework\TestCase;
require "parse_json.php";

final class parse_json_test extends TestCase
{
    public function testJsonToMetadataObject(): void
    {
        $json = '{
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
                }
            }
        }';
        $converted = json_to_metadata_object($json);

        // Build expected object
        $expected = new Metadata();
        $expected->type = Timescale::Daily;
        $expected->symbol = "AAPL";
        $expected->update_time = "2025-10-17";
        $expected->size = "Compact";
        $expected->timezone = "US/Eastern";

        $this->assertEquals($converted, $expected);
    }
}

?>
