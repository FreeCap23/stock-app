<?php
use PHPUnit\Framework\TestCase;
require_once "parse_json.php";

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

    public function testJsonToOhlcvObject(): void
    {
        $json = '{
                "2025-10-17": {
                    "1. open": "248.0200",
                    "2. high": "253.3800",
                    "3. low": "247.2700",
                    "4. close": "252.2900",
                    "5. volume": "49146961"
                }
            }';
        $converted = json_to_ohlcv_object($json);

        // Build expected object
        $expected = new OHLCV();
        $expected->date = "2025-10-17";
        $expected->open = "248.0200";
        $expected->high = "253.3800";
        $expected->low = "247.2700";
        $expected->close = "252.2900";
        $expected->volume = "49146961";

        $this->assertEquals($converted, $expected);
    }

    public function testJsonToOhlcvArray(): void
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
                },
                "2025-10-16": {
                    "1. open": "247.0200",
                    "2. high": "252.3800",
                    "3. low": "246.2700",
                    "4. close": "251.2900",
                    "5. volume": "49146962"
                }
            }
        }';
        $converted = json_to_ohlcv_array($json);

        $this->assertNotNull($converted);
        $this->assertIsArray($converted);
        $this->assertCount(2, $converted);

        // Check first OHLCV object
        $this->assertEquals("2025-10-17", $converted[0]->date);
        $this->assertEquals("248.0200", $converted[0]->open);
        $this->assertEquals("253.3800", $converted[0]->high);
        $this->assertEquals("247.2700", $converted[0]->low);
        $this->assertEquals("252.2900", $converted[0]->close);
        $this->assertEquals("49146961", $converted[0]->volume);

        // Check second OHLCV object
        $this->assertEquals("2025-10-16", $converted[1]->date);
        $this->assertEquals("247.0200", $converted[1]->open);
        $this->assertEquals("252.3800", $converted[1]->high);
        $this->assertEquals("246.2700", $converted[1]->low);
        $this->assertEquals("251.2900", $converted[1]->close);
        $this->assertEquals("49146962", $converted[1]->volume);
    }

    public function testJsonToOhlcvArrayWithError(): void
    {
        $json = '{
            "Error Message": "Invalid API call. Please retry or visit the documentation (https://www.alphavantage.co/documentation/) for TIME_SERIES_DAILY."
        }';
        $converted = json_to_ohlcv_array($json);

        $this->assertNull($converted);
    }

    public function testJsonToOhlcvArrayWithNote(): void
    {
        $json = '{
            "Note": "Thank you for using Alpha Vantage! Our standard API call frequency is 5 calls per minute and 500 calls per day. Please visit https://www.alphavantage.co/premium/ if you would like to target a higher API call frequency."
        }';
        $converted = json_to_ohlcv_array($json);

        $this->assertNull($converted);
    }
}

?>
