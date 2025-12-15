<?php

declare(strict_types=1);

// TODO: Move this function in the Tiingo class
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

    if ($array === null) {
        throw new InvalidArgumentException("Invalid JSON string: $json_string \njson_decode returned a null array");
    }
    if (!isset($array["ticker"])) {
        throw new InvalidArgumentException("Invalid Tiingo meta JSON format. ticker key not found");
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
