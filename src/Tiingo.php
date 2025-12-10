<?php

declare(strict_types=1);

namespace stock_app;

use UnexpectedValueException;
use InvalidArgumentException;
use DateTime;

class Tiingo implements StockMarketDataProvider
{
    private string $api_key = "";
    private string $base_url = "https://api.tiingo.com/tiingo/daily/";

    /*
    * Make a request to Tiingo.
    *
    * Note: API Key is not needed in $parameters
    * Note: If response format is not specified, json will be used
    *
    * @param string $method The method to call, for example "prices"
    * @param array $parameters Optional parameters for the method
    *
    * @throws UnexpectedValueException if API response is null
    * @throws InvalidArgumentException if format is neither json nor csv
    * @throws RuntimeException if the api key hasn't been set or if it's empty
    *
    * @api
    */
    public function makeRequest(string $method, array $parameters = []): string
    {
        if ($this->api_key == "") {
            throw new RuntimeException("Invalid or null api key. Did you forget to set it with setApiKey()?");
        }

        if (isset($parameters["token"]) == false) {
            $parameters["token"] = $this->api_key;
        }

        if (isset($parameters["format"]) == false) {
            $parameters["format"] = "json";
        } elseif ($parameters["format"] == "json") {
            $content_type = "application/json";
        } elseif ($parameters["format"] == "csv") {
            $content_type = "text/csv";
        } else {
            throw new InvalidArgumentException("Invalid response format " . $parameters["format"]);
        }

        $url = $this->base_url . "/" . $method . "?" . http_build_query($parameters);

        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Content-Type: " . $content_type . "\r\n",
            ],
        ]);

        $response = file_get_contents($url, context: $context);
        if ($response == false) {
            throw new UnexpectedValueException("Error making request. file_get_contents returned false.");
        }

        return $response;
    }

    /**
    * Converts a json OHLCV string from Tiingo to an OHLCV object
    *
    * @param array $array The OHLCV array, in the format below
    *
    * @return OHLCV
    *
    * This is how the function expects the array to look like:
    * array(13) {
        ["date"]=>
        string(24) "2019-01-02T00:00:00.000Z"
        ["close"]=>
        float(157.92)
        ["high"]=>
        float(158.85)
        ["low"]=>
        float(154.23)
        ["open"]=>
        float(154.89)
        ["volume"]=>
        int(37039737)
        ["adjClose"]=>
        float(157.92)
        ["adjHigh"]=>
        float(158.85)
        ["adjLow"]=>
        float(154.23)
        ["adjOpen"]=>
        float(154.89)
        ["adjVolume"]=>
        int(37039737)
        ["divCash"]=>
        float(0)
        ["splitFactor"]=>
        float(1)
    * }
    *
    */
    private function ohlcvArrayToObject(array $array): OHLCV
    {
        // Extract date from ISO timestamp (2019-01-02T00:00:00.000Z -> 2019-01-02)
        $date_string = $array["date"];
        $date = substr($date_string, 0, 10); // Extract YYYY-MM-DD from ISO format

        $ohlcv = new OHLCV();
        $ohlcv->date = $date;
        $ohlcv->open = (string) $array["open"];
        $ohlcv->high = (string) $array["high"];
        $ohlcv->low = (string) $array["low"];
        $ohlcv->close = (string) $array["close"];
        $ohlcv->volume = (string) $array["volume"];

        return $ohlcv;
    }

    /**
    * Sets the API key, checking for the correct character count & making a test request
    *
    * @param string $key Tiingo API Key
    *
    * @throws InvalidArgumentException if $key is other than 40 characters long
    * @throws TiingoException if an exception is caught when testing the key
    *
    * @return void
    * @api
    */
    public function setApiKey(string $key): void
    {
        if (strlen($key) != 40) {
            throw new InvalidArgumentException("Invalid API key provided."
                . "The API key must be exactly 40 characters long");
        }

        $this->api_key = $key;

        try {
            $response = $this->makeRequest("test");
            if ($response !== '{"message": "You successfully sent a request"}') {
                throw new UnexpectedValueException("Error setting API key. It looks like your key is invalid.\n" . $response);
            }
            $this->api_key = $response;
        } catch (UnexpectedValueException $e) {
            throw new TiingoException("Error making request to Tiingo. Received null response", 0, $e);
        } catch (InvalidArgumentException $e) {
            throw new TiingoException("Error making request to Tiingo. Invalid response format provided", 0, $e);
        }
    }

    /**
    * @return OHLCV[]
    */
    public function getOhlcv(string $ticker, DateTime $start_date, DateTime $end_date): array
    {
        if ($ticker == "") {
            throw new InvalidArgumentException("Provided empty ticker string");
        }

        $parameters = [
            "format" => "json",
            "startDate" => $start_date->format('Y-m-d'),
            "endDate" => $end_date->format('Y-m-d'),
        ];

        $response = "";

        try {
            $response = $this->makeRequest("prices", $parameters);
        } catch (UnexpectedValueException $e) {
            throw new TiingoException("Error making request to Tiingo. Received null response", 0, $e);
        } catch (InvalidArgumentException $e) {
            throw new TiingoException("Error making request to Tiingo. Invalid response format provided", 0, $e);
        }

        if ($response == "") {
            throw new TiingoException("Received null response");
        }

        $array = json_decode($response, associative: true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new TiingoException("Failed to decode JSON response: " . json_last_error_msg());
        }

        // Check if API returned an error message inside valid JSON
        // For example {"detail": "Error message"}
        if (isset($array["detail"])) {
            throw new TiingoException("Tiingo API error: " . $array["detail"]);
        }

        $ohlcv_object_array = [];
        foreach ($array as $day_data) {
            $day_ohlcv = $this->jsonOhlcvToObject($day_data);
            $ohlcv_object_array[] = $day_ohlcv;
        }

        return $ohlcv_object_array;
    }

    public function getMetadata(string $ticker): Metadata
    {
        // TODO:
    }
}
