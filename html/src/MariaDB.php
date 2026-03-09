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
        $sql = "SELECT date, open, high, low, close, volume
                    FROM daily_ohlcv
                    WHERE symbol = :symbol
                      AND date >= :start_date
                      AND date <= :end_date
                    ORDER BY date ASC";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                "symbol" => $symbol,
                "start_date" => $startDate,
                "end_date" => $endDate,
            ]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $ohlcv_array = [];
            foreach ($results as $day) {
                $ohlcv_array[] = $this->ohlcvArrayToObject($day);
            }
            return $ohlcv_array;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    public function getExistingDates(
        string $symbol,
        string $startDate,
        string $endDate,
    ): array {
        $sql = "SELECT date
                    FROM daily_ohlcv
                    WHERE symbol = :symbol
                      AND date >= :start_date
                      AND date <= :end_date
                    ORDER BY date ASC";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                "symbol" => $symbol,
                "start_date" => $startDate,
                "end_date" => $endDate,
            ]);
            // PDO::FETCH_COLUMN returns a flat array of the first selected column
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            return $results;
        } catch (PDOException $e) {
            // Returning an empty array is safer here. If the DB fails,
            // the main script will assume no dates exist and try to fetch from the API.
            error_log($e->getMessage());
            return [];
        }
    }

    public function login(string $user, string $pass): string
    {
        $sql = "SELECT id, password FROM users WHERE username = :username";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute(["username" => $user]);

            // Fetch the user's row. PDO::FETCH_ASSOC returns it as an associative array.
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData && password_verify($pass, $userData["password"])) {
                return (string) $userData["id"];
            }

            // If the user doesn't exist, or the password was wrong, return an empty string.
            return "";
        } catch (PDOException $e) {
            // If the database query fails for some reason, return empty string.
            return "";
        }
    }

    public function register(string $user, string $pass): string
    {
        $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

        $currentDate = date("Y-m-d");

        $sql = "INSERT INTO users (username, password, date_registered)
                VALUES (:username, :password, :date_registered)";

        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute([
                "username" => $user,
                "password" => $hashedPassword,
                "date_registered" => $currentDate,
            ]);

            $newId = $this->connection->lastInsertId();
            return (string) $newId;
        } catch (PDOException $e) {
            return "";
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
}
