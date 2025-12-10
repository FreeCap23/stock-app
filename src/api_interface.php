<?php

declare(strict_types=1);

namespace stock_app;

interface StockMarketDataProvider
{
    public function getOhlcv(string $ticker, DateTime $start_date, DateTime $end_date): array

    public function getMetadata(string $ticker): Metadata
}
