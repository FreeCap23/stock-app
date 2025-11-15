<?php
use PHPUnit\Framework\TestCase;
require_once "ingest_data.php";

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

    public function testSafeIngestOhlcv()
    {
        $ohlcv = new OHLCV();
        $ohlcv->date = "2025-10-18";
        $ohlcv->open = "249.0200";
        $ohlcv->high = "254.3800";
        $ohlcv->low = "248.2700";
        $ohlcv->close = "253.2900";
        $ohlcv->volume = "49146963";
        $symbol = "TEST2";

        // First insert should succeed
        $this->assertTrue(safe_ingest_ohlcv($ohlcv, $symbol));

        // Second insert (duplicate) should not fail but return true (INSERT IGNORE)
        $this->assertTrue(safe_ingest_ohlcv($ohlcv, $symbol));

        cleanupTestRow($ohlcv->date, $symbol);
    }

    public function testIngestOhlcvArray()
    {
        $ohlcv1 = new OHLCV();
        $ohlcv1->date = "2025-10-19";
        $ohlcv1->open = "250.0200";
        $ohlcv1->high = "255.3800";
        $ohlcv1->low = "249.2700";
        $ohlcv1->close = "254.2900";
        $ohlcv1->volume = "49146964";

        $ohlcv2 = new OHLCV();
        $ohlcv2->date = "2025-10-20";
        $ohlcv2->open = "251.0200";
        $ohlcv2->high = "256.3800";
        $ohlcv2->low = "250.2700";
        $ohlcv2->close = "255.2900";
        $ohlcv2->volume = "49146965";

        $symbol = "TEST3";
        $ohlcv_array = [$ohlcv1, $ohlcv2];

        $ingested_count = ingest_ohlcv_array($ohlcv_array, $symbol);
        $this->assertGreaterThanOrEqual(2, $ingested_count);

        cleanupTestRow($ohlcv1->date, $symbol);
        cleanupTestRow($ohlcv2->date, $symbol);
    }

    public function testFetchOhlcvData()
    {
        // First insert some test data
        $ohlcv = new OHLCV();
        $ohlcv->date = "2025-10-21";
        $ohlcv->open = "252.0200";
        $ohlcv->high = "257.3800";
        $ohlcv->low = "251.2700";
        $ohlcv->close = "256.2900";
        $ohlcv->volume = "49146966";
        $symbol = "TEST4";

        safe_ingest_ohlcv($ohlcv, $symbol);

        // Fetch the data
        $data = fetch_ohlcv_data($symbol);
        $this->assertNotNull($data);
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));

        // Check that our inserted data is in the results
        $found = false;
        foreach ($data as $row) {
            if ($row['date'] === $ohlcv->date) {
                $found = true;
                $this->assertEquals($ohlcv->open, $row['open']);
                $this->assertEquals($ohlcv->high, $row['high']);
                $this->assertEquals($ohlcv->low, $row['low']);
                $this->assertEquals($ohlcv->close, $row['close']);
                $this->assertEquals($ohlcv->volume, $row['volume']);
                break;
            }
        }
        $this->assertTrue($found, "Inserted data should be found in fetched results");

        cleanupTestRow($ohlcv->date, $symbol);
    }
}

?>
