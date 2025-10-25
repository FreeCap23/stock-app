<?php
use PHPUnit\Framework\TestCase;
require "ingest_data.php";

function cleanupTestRow(string $date, string $symbol)
{
    $conn = create_sql_connection();
    if (!is_a($conn, "PDO")) {
        die("Failed to create database connection! $conn");
    }

    $sql = "DELETE FROM daily_ohlcv WHERE daily_ohlcv.symbol = \"$symbol\" AND daily_ohlcv.date = \"$date\";";
    try {
        $conn->exec($sql);
    } catch (PDOException $e) {
        die("SQL Query $sql failed!\n" . $e->getMessage());
    }

    // Close the connection
    $conn = null;
}

final class ingest_data_test extends TestCase
{
    public function testInsertDailyOhlcv()
    {
        $ohlcv = new OHLCV();
        $ohlcv->date = "2025-10-17";
        $ohlcv->open = "248.0200";
        $ohlcv->high = "253.3800";
        $ohlcv->low = "247.2700";
        $ohlcv->close = "252.2900";
        $ohlcv->volume = "49146961";
        $symbol = "TEST";

        $this->assertSame(ingest_ohlcv($ohlcv, $symbol), 0);

        cleanupTestRow($ohlcv->date, $symbol);
    }
}

?>
