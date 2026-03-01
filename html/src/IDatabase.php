<?php

declare(strict_types=1);

namespace stock_app;

interface IDatabase
{
    public function initConnection(
        string $serverName,
        string $databaseName,
        string $user,
        string $password,
    ): string;

    public function ingestOhlcvArray(array $ohlcvArray, string $symbol): int;

    public function fetchOhlcvData(
        string $symbol,
        string $startDate,
        string $endDate,
    ): ?array;

    public function getExistingDates(
        string $symbol,
        string $startDate,
        string $endDate,
    ): array;
}
