<?php

declare(strict_types=1);

namespace stock_app;

require __DIR__ . '/vendor/autoload.php';

use InvalidArgumentException;
use RuntimeException;
use DateInterval;
use DateTime;

$error_message = null;
$start_date = isset($_GET["start_date"]) ? htmlspecialchars($_GET["start_date"]) : "";
$end_date = isset($_GET["end_date"]) ? htmlspecialchars($_GET["end_date"]) : "";
$symbol = isset($_GET["symbol"]) ? htmlspecialchars($_GET["symbol"]) : "";
$tiingo = new Tiingo();
if (array_key_exists("TIINGO_API_TOKEN", $_ENV)) {
    $tiingo->setApiKey($_ENV["TIINGO_API_TOKEN"]);
} else {
    throw new RuntimeException("Tiingo API key environment variable not found!");
}

if ($symbol && $start_date && $end_date) {
    // TODO: First, try to fetch from database

    // If no data found, fetch from Tiingo and ingest
    // // TODO: update this condition after implementing fetching from local database
    if (true) {
        try {
            $start = DateTime::createFromFormat("Y-m-d", $start_date);
            $end = DateTime::createFromFormat("Y-m-d", $end_date);
            $ohlcv_array = $tiingo->getOhlcv($symbol, $start, $end);
        } catch (TiingoException|InvalidArgumentException $e) {
            $error_message = $e->getMessage();
        }
    }
}
?>
<html>
    <head>
        <title>Stock App - Candlestick Chart</title>
        <script src="https://cdn.plot.ly/plotly-2.32.0.min.js"></script>
        <link rel="stylesheet" href="styles.css">
    </head>

    <body>
        <div class="container">
            <div class="form-container">
                <form action="" method="GET">
                    <label for="symbol">Stock Symbol:</label>
                    <input
                        type="text"
                        name="symbol"
                        id="symbol"
                        value="<?php echo $symbol; ?>"
                        placeholder="e.g. AAPL"
                        required
                    >
                    <label for="start_date">Start date: </label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        max=<?php echo date_create()->sub(DateInterval::createFromDateString("1 day"))->format("Y-m-d")?>
                        <?php if ($start_date == "") : ?>
                            value=<?php echo date_create()->sub(DateInterval::createFromDateString("1 day"))->format("Y-m-d")?>
                        <?php else : ?>
                            value=<?php echo $start_date?>
                        <?php endif ?>
                        required
                    >
                    <label for="end_date">End date: </label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        <?php if ($end_date == "") : ?>
                            value=<?php echo date_create()->format("Y-m-d")?>
                        <?php else : ?>
                            value=<?php echo $end_date?>
                        <?php endif ?>
                        required
                    >
                    <input type="submit" value="Load Chart">
                </form>
            </div>

            <?php if ($symbol) : ?>
                <?php if ($error_message) : ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php else : ?>
                    <?php
                    $chart_dates = [];
                    $chart_opens = [];
                    $chart_highs = [];
                    $chart_lows = [];
                    $chart_closes = [];
                    foreach ($ohlcv_array as $ohlcv) {
                        $chart_dates[] = $ohlcv->date;
                        $chart_opens[] = $ohlcv->open;
                        $chart_highs[] = $ohlcv->high;
                        $chart_lows[] = $ohlcv->low;
                        $chart_closes[] = $ohlcv->close;
                    }
                    ?>
                    <div id="chartContainer" class="chart-container"></div>
                    <script>
                        window.onload = function () {
                            const trace = {
                                x: <?php echo json_encode($chart_dates); ?>.map(date => new Date(date)),
                                open: <?php echo json_encode($chart_opens); ?>,
                                high: <?php echo json_encode($chart_highs); ?>,
                                low: <?php echo json_encode($chart_lows); ?>,
                                close: <?php echo json_encode($chart_closes); ?>,
                                type: "candlestick",
                                increasing: { line: { color: "#2e7d32" } },
                                decreasing: { line: { color: "#c62828" } }
                            };
                            const layout = {
                                title: "<?php echo $symbol; ?> - Daily Candlestick Chart",
                                xaxis: {
                                    title: "Date",
                                    type: "date",
                                    rangeslider: { visible: true },
                                    tickangle: -45
                                },
                                yaxis: {
                                    title: "Price (USD)",
                                    tickprefix: "$",
                                    side: "right"
                                },
                                margin: { t: 50, r: 30, b: 50, l: 60 }
                            };
                            Plotly.newPlot("chartContainer", [trace], layout, { responsive: true });
                        }
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </body>
</html>
