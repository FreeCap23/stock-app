<?php

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\TestCase;
use stock_app\Tiingo;
use stock_app\TiingoException;

class TiingoTests extends TestCase
{
    public function testSetApiKeyExecutesSuccessfullyWithValidKey()
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

    public function testSetApiKeyThrowsInvalidArgumentException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $API_KEY = "mock-api-key-NOT-40-characters-long";
        $tiingo = new Tiingo();
        $tiingo->setApiKey($API_KEY);
    }

    public function testSetApiKeyThrowsTiingoException()
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

    public function testGetOhlcvThrowsInvalidArgumentExceptionWithEmptyTickerArgument()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-07");

        $tiingo = new Tiingo();
        $tiingo->getOhlcv("", $start_date, $end_date);
    }

    public function testGetOhlcvThrowsInvalidArgumentExceptionWithStartDateAfterEndDate()
    {
        $this->expectException(\InvalidArgumentException::class);

        // Prepare function arguments
        $start_date = \DateTime::createFromFormat("Y-m-d", "2019-01-02");
        $end_date = \DateTime::createFromFormat("Y-m-d", "2019-01-01");

        $tiingo = new Tiingo();
        $tiingo->getOhlcv("RNDM", $start_date, $end_date);
    }
}
