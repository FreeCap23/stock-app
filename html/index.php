<?php

declare(strict_types=1);

namespace stock_app;

require __DIR__ . '/vendor/autoload.php';

use InvalidArgumentException;
use RuntimeException;
use DateInterval;

$error_message = null;
$symbol = isset($_GET["symbol"]) ? htmlspecialchars($_GET["symbol"]) : "";
$tiingo = new Tiingo();
if (array_key_exists("TIINGO_API_TOKEN", $_ENV)) {
    $tiingo->setApiKey($_ENV["TIINGO_API_TOKEN"]);
} else {
    throw new RuntimeException("Tiingo API key environment variable not found!");
}

if ($symbol) {
    // TODO: First, try to fetch from database

    // If no data found, fetch from Tiingo and ingest
    // // TODO: update this condition after implementing fetching from local database
    if (true) {
        $end_date = date_create();

        // TODO: Add method to let the user select the interval
        $start_date = clone $end_date;
        $start_date->sub(DateInterval::createFromDateString("1 month"));

        try {
            $ohlcv_array = $tiingo->getOhlcv($symbol, $start_date, $end_date);
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
                    <input type="text" name="symbol" id="symbol" value="<?php echo $symbol; ?>" placeholder="e.g., AAPL" required>
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
