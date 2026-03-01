<?php

namespace stock_app;
use PDO;
use PDOException;

class MariaDB implements IDatabase
{
    private $connection;

    public function initConnection(
        string $serverName,
        string $databaseName,
        string $user,
        string $password,
    ): string {
        try {
            // Create connection
            $this->connection = new PDO(
                "mysql:host=" . $serverName . ";dbname=" . $databaseName,
                $user,
                $password,
            );
            // Set the PDO error mode to exception
            $this->connection->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION,
            );
            return "Connection successful";
        } catch (PDOException $e) {
            return $e->getMessage();
        }
    }

    public function ingestOhlcvArray(array $ohlcvArray, string $symbol): int
    {
        $ingestedCount = 0;

        foreach ($ohlcvArray as $ohlcv) {
            if ($this->ingestOhlcv($ohlcv, $symbol)) {
                $ingestedCount++;
            }
        }

        return $ingestedCount;
    }

    public function fetchOhlcvData(
        string $symbol,
        string $startDate,
        string $endDate,
    ): ?array {
        // TODO: Update to use new arguments
        $sql = "SELECT date, open, high, low, close, volume
                    FROM daily_ohlcv
                    WHERE symbol = :symbol
                    ORDER BY date ASC";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(["symbol" => $symbol]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $results;
        } catch (PDOException $e) {
            return null;
        }
    }

    private function ingestOhlcv(OHLCV $ohlcv, string $symbol): bool
    {
        // Use INSERT IGNORE to handle duplicates gracefully
        $sql = "INSERT IGNORE INTO daily_ohlcv (symbol, date, open, high, low, close, volume)
                VALUES (:symbol, :date, :open, :high, :low, :close, :volume)";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                "symbol" => $symbol,
                "date" => $ohlcv->date,
                "open" => $ohlcv->open,
                "high" => $ohlcv->high,
                "low" => $ohlcv->low,
                "close" => $ohlcv->close,
                "volume" => $ohlcv->volume,
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
