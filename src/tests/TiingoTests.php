<?php

declare(strict_types=1);

namespace tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stock_app\Tiingo;
use stock_app\TiingoException;
use stock_app\OHLCV;

class TiingoTests extends TestCase
{
    public function testSetApiKeyExecutesSuccessfullyWithValidKey(): void
    {
        $API_KEY = "mock-api-key-40-characters-long000000000";

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the setApiKey() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("test")
               ->willReturn('{"message": "You successfully sent a request"}');

        $tiingo->setApiKey($API_KEY);
    }

    public function testSetApiKeyThrowsInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $API_KEY = "mock-api-key-NOT-40-characters-long";
        $tiingo = new Tiingo();
        $tiingo->setApiKey($API_KEY);
    }

    public function testSetApiKeyThrowsTiingoException(): void
    {
        $this->expectException(TiingoException::class);

        $API_KEY = "mock-api-key-40-characters-long000000000";

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the setApiKey() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("test")
               ->willReturn('{"message": "Auth Token was not correct"}');

        $tiingo->setApiKey($API_KEY);
    }

    public function testGetOhlcvThrowsInvalidArgumentExceptionWithEmptyTickerArgument(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $tiingo = new Tiingo();
        $tiingo->getOhlcv("", $start_date, $end_date);
    }

    public function testGetOhlcvThrowsInvalidArgumentExceptionWithStartDateAfterEndDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-01");

        $tiingo = new Tiingo();
        $tiingo->getOhlcv("RNDM", $start_date, $end_date);
    }

    public function testGetOhlcvThrowsTiingoExceptionWithNullResponse(): void
    {
        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $makeRequestParameters = [
            "format" => "json",
            "startDate" => $start_date->format('Y-m-d'),
            "endDate" => $end_date->format('Y-m-d'),
        ];

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the getOhlcv() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("prices", "TICKER", $makeRequestParameters)
               ->willReturn("");

        try {
            $tiingo->getOhlcv("TICKER", $start_date, $end_date);
        } catch (TiingoException $e) {
            $this->assertEquals("Received null response", $e->getMessage());
        }
    }

    public function testGetOhlcvThrowsTiingoExceptionWithInvalidJsonResponse(): void
    {
        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $makeRequestParameters = [
            "format" => "json",
            "startDate" => $start_date->format('Y-m-d'),
            "endDate" => $end_date->format('Y-m-d'),
        ];

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the getOhlcv() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("prices", "TICKER", $makeRequestParameters)
               ->willReturn("this is not a valid json string");

        try {
            $tiingo->getOhlcv("TICKER", $start_date, $end_date);
        } catch (TiingoException $e) {
            $this->assertStringStartsWith("Failed to decode JSON response: ", $e->getMessage());
        }
    }

    public function testGetOhlcvThrowsTiingoExceptionWithErrorInJsonResponse(): void
    {
        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $makeRequestParameters = [
            "format" => "json",
            "startDate" => $start_date->format('Y-m-d'),
            "endDate" => $end_date->format('Y-m-d'),
        ];

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the getOhlcv() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("prices", "TICKER", $makeRequestParameters)
               ->willReturn('{"detail":"Error: Ticker \'TICKER\' not found"}');

        try {
            $tiingo->getOhlcv("TICKER", $start_date, $end_date);
        } catch (TiingoException $e) {
            $this->assertStringStartsWith("Tiingo API error: ", $e->getMessage());
        }
    }

    public function testGetOhlcvReturnsCorrectValue(): void
    {
        // Actual JSON response for this request
        $response = <<<JSON
                       [{"date":"2019-01-02T00:00:00.000Z","close":110.23,"high":110.3248,"low":108.1,"open":110.02,"volume":378784,"adjClose":110.23,"adjHigh":110.3248,"adjLow":108.1,"adjOpen":110.02,"adjVolume":378784,"divCash":0.0,"splitFactor":1.0},{"date":"2019-01-03T00:00:00.000Z","close":107.49,"high":109.93,"low":107.42,"open":109.62,"volume":195338,"adjClose":107.49,"adjHigh":109.93,"adjLow":107.42,"adjOpen":109.62,"adjVolume":195338,"divCash":0.0,"splitFactor":1.0},{"date":"2019-01-04T00:00:00.000Z","close":110.02,"high":110.11,"low":108.03,"open":108.18,"volume":189505,"adjClose":110.02,"adjHigh":110.11,"adjLow":108.03,"adjOpen":108.18,"adjVolume":189505,"divCash":0.0,"splitFactor":1.0},{"date":"2019-01-07T00:00:00.000Z","close":110.75,"high":111.55,"low":109.56,"open":109.56,"volume":158804,"adjClose":110.75,"adjHigh":111.55,"adjLow":109.56,"adjOpen":109.56,"adjVolume":158804,"divCash":0.0,"splitFactor":1.0}]
                       JSON;

        // Build expected OHLCV array
        $obj1 = new OHLCV();
        $obj1->date = '2019-01-02';
        $obj1->open = '110.02';
        $obj1->high = '110.3248';
        $obj1->low = '108.1';
        $obj1->close = '110.23';
        $obj1->volume = '378784';

        $obj2 = new OHLCV();
        $obj2->date = '2019-01-03';
        $obj2->open = '109.62';
        $obj2->high = '109.93';
        $obj2->low = '107.42';
        $obj2->close = '107.49';
        $obj2->volume = '195338';

        $obj3 = new OHLCV();
        $obj3->date = '2019-01-04';
        $obj3->open = '108.18';
        $obj3->high = '110.11';
        $obj3->low = '108.03';
        $obj3->close = '110.02';
        $obj3->volume = '189505';

        $obj4 = new OHLCV();
        $obj4->date = '2019-01-07';
        $obj4->open = '109.56';
        $obj4->high = '111.55';
        $obj4->low = '109.56';
        $obj4->close = '110.75';
        $obj4->volume = '158804';

        $expected = [$obj1, $obj2, $obj3, $obj4];

        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $makeRequestParameters = [
            "format" => "json",
            "startDate" => $start_date->format('Y-m-d'),
            "endDate" => $end_date->format('Y-m-d'),
        ];

        $tiingo = $this->getMockBuilder(Tiingo::class)
                       ->onlyMethods(["makeRequest"])
                       ->disableOriginalConstructor()
                       ->getMock();

        // Setup the expectation for the getOhlcv() method
        $tiingo->expects($this->once())
               ->method("makeRequest")
               ->with("prices", "BFAM", $makeRequestParameters)
               ->willReturn($response);

        // Intentionally don't try catch this method so we know what went wrong if it does throw an exception.
        $actual = $tiingo->getOhlcv("BFAM", $start_date, $end_date); // Ticker was chosen at random using a website
        $this->assertEquals($expected, $actual);
    }

    //TODO: Write tests for RuntimeException of makeRequest method
}
