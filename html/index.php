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
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f5f5f5;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .form-container {
                margin-bottom: 30px;
                text-align: center;
            }
            .form-container form {
                display: inline-block;
            }
            .form-container input[type="text"] {
                padding: 10px;
                font-size: 16px;
                border: 2px solid #ddd;
                border-radius: 4px;
                margin-right: 10px;
                width: 200px;
            }
            .form-container input[type="submit"] {
                padding: 10px 20px;
                font-size: 16px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .form-container input[type="submit"]:hover {
                background-color: #45a049;
            }
            .chart-container {
                width: 100%;
                height: 500px;
                margin-top: 20px;
            }
            .error-message {
                text-align: center;
                color: #d32f2f;
                padding: 20px;
                background-color: #ffebee;
                border-radius: 4px;
                margin-top: 20px;
            }
        </style>
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
