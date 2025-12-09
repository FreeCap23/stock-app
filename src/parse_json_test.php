<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once "parse_json.php";

final class parse_json_test extends TestCase
{
    public function testJsonToMetadataObject(): void
    {
        $json = '{
            "ticker": "AAPL",
            "name": "Apple Inc",
            "description": "Apple Inc. (Apple) designs, manufactures and markets mobile communication and media devices, personal computers, and portable digital music players, and a variety of related software, services, peripherals, networking solutions, and third-party digital content and applications. The Company\'s products and services include iPhone, iPad, Mac, iPod, Apple TV, a portfolio of consumer and professional software applications, the iOS and OS X operating systems, iCloud, and a variety of accessory, service and support offerings. The Company also delivers digital content and applications through the iTunes Store, App StoreSM, iBookstoreSM, and Mac App Store. The Company distributes its products worldwide through its retail stores, online stores, and direct sales force, as well as through third-party cellular network carriers, wholesalers, retailers, and value-added resellers. In February 2012, the Company acquired app-search engine Chomp.",
            "startDate": "1980-12-12",
            "endDate": "2025-12-05",
            "exchangeCode": "NASDAQ"
        }';
        $converted = json_to_metadata_object($json);

        // Build expected object
        $expected = new Metadata();
        $expected->type = Timescale::Daily;
        $expected->ticker = "AAPL";
        $expected->name = "Apple Inc";
        $expected->description = "Apple Inc. (Apple) designs, manufactures and markets mobile communication and media devices, personal computers, and portable digital music players, and a variety of related software, services, peripherals, networking solutions, and third-party digital content and applications. The Company's products and services include iPhone, iPad, Mac, iPod, Apple TV, a portfolio of consumer and professional software applications, the iOS and OS X operating systems, iCloud, and a variety of accessory, service and support offerings. The Company also delivers digital content and applications through the iTunes Store, App StoreSM, iBookstoreSM, and Mac App Store. The Company distributes its products worldwide through its retail stores, online stores, and direct sales force, as well as through third-party cellular network carriers, wholesalers, retailers, and value-added resellers. In February 2012, the Company acquired app-search engine Chomp.";
        $expected->startDate = "1980-12-12";
        $expected->endDate = "2025-12-05";
        $expected->exchangeCode = "NASDAQ";

        $this->assertEquals($converted, $expected);
    }

    public function testJsonToOhlcvObject(): void
    {
        $json = '[{
            "date": "2025-10-17T00:00:00.000Z",
            "close": 252.29,
            "high": 253.38,
            "low": 247.27,
            "open": 248.02,
            "volume": 49146961,
            "adjClose": 12.3329825953,
            "adjHigh": 12.3710704972,
            "adjLow": 12.2661038384,
            "adjOpen": 12.278100028,
            "adjVolume": 302221102,
            "divCash": 0.0,
            "splitFactor": 1.0
        }]';
        $converted = json_to_ohlcv_object($json);

        // Build expected object
        $expected = new OHLCV();
        $expected->date = "2025-10-17";
        $expected->open = "248.02";
        $expected->high = "253.38";
        $expected->low = "247.27";
        $expected->close = "252.29";
        $expected->volume = "49146961";

        $this->assertEquals($converted, $expected);
    }

    public function testJsonToOhlcvArray(): void
    {
        $json = '[{
            "date": "2025-10-17T00:00:00.000Z",
            "close": 252.29,
            "high": 253.38,
            "low": 247.27,
            "open": 248.02,
            "volume": 49146961,
            "adjClose": 12.3329825953,
            "adjHigh": 12.3710704972,
            "adjLow": 12.2661038384,
            "adjOpen": 12.278100028,
            "adjVolume": 302221102,
            "divCash": 0.0,
            "splitFactor": 1.0
        }, {
            "date": "2025-10-16T00:00:00.000Z",
            "close": 251.29,
            "high": 252.38,
            "low": 246.27,
            "open": 247.02,
            "volume": 49146962,
            "adjClose": 12.3329825953,
            "adjHigh": 12.3710704972,
            "adjLow": 12.2661038384,
            "adjOpen": 12.278100028,
            "adjVolume": 302221102,
            "divCash": 0.0,
            "splitFactor": 1.0
        }]';
        $converted = json_to_ohlcv_array($json);

        $this->assertNotNull($converted);
        $this->assertIsArray($converted);
        $this->assertCount(2, $converted);

        // Check first OHLCV object
        $this->assertEquals("2025-10-17", $converted[0]->date);
        $this->assertEquals("248.02", $converted[0]->open);
        $this->assertEquals("253.38", $converted[0]->high);
        $this->assertEquals("247.27", $converted[0]->low);
        $this->assertEquals("252.29", $converted[0]->close);
        $this->assertEquals("49146961", $converted[0]->volume);

        // Check second OHLCV object
        $this->assertEquals("2025-10-16", $converted[1]->date);
        $this->assertEquals("247.02", $converted[1]->open);
        $this->assertEquals("252.38", $converted[1]->high);
        $this->assertEquals("246.27", $converted[1]->low);
        $this->assertEquals("251.29", $converted[1]->close);
        $this->assertEquals("49146962", $converted[1]->volume);
    }

    public function testJsonToOhlcvArrayWithError(): void
    {
        $json = '{
            "detail": "Invalid API token"
        }';
        $converted = json_to_ohlcv_array($json);

        $this->assertNull($converted);
    }

    public function testJsonToOhlcvArrayWithNote(): void
    {
        $json = '[]';
        $converted = json_to_ohlcv_array($json);

        $this->assertNotNull($converted);
        $this->assertIsArray($converted);
        $this->assertCount(0, $converted);
    }
}
