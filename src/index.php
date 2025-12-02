<?php
require_once "ingest_data.php";
require_once "parse_json.php";

$symbol = isset($_GET["symbol"]) ? htmlspecialchars($_GET["symbol"]) : "";
$data = null;
$error_message = null;
$chart_dates = [];
$chart_opens = [];
$chart_highs = [];
$chart_lows = [];
$chart_closes = [];

if ($symbol) {
    // First, try to fetch from database
    $data = fetch_ohlcv_data($symbol);

    // If no data found, fetch from Alpha Vantage and ingest
    if ($data !== null && empty($data)) {
        $api_response = fetch_from_alphavantage($symbol);
        if ($api_response !== null) {
            $ohlcv_array = json_to_ohlcv_array($api_response);
            if ($ohlcv_array !== null && !empty($ohlcv_array)) {
                $ingested_count = ingest_ohlcv_array($ohlcv_array, $symbol);
                if ($ingested_count > 0) {
                    // Fetch again from database after ingesting
                    $data = fetch_ohlcv_data($symbol);
                } else {
                    $error_message = "Failed to ingest data into database.";
                }
            } else {
                $error_message = "Failed to parse data from Alpha Vantage API. The symbol may be invalid or the API may be rate-limited.";
            }
        } else {
            $error_message = "Failed to fetch data from Alpha Vantage API. Please check your API key and connection.";
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

            <?php if ($symbol): ?>
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php elseif ($data === null): ?>
                    <div class="error-message">
                        Error fetching data. Please check your database connection.
                    </div>
                <?php elseif (empty($data)): ?>
                    <div class="error-message">
                        No data found for symbol "<?php echo $symbol; ?>". Please check the symbol and try again.
                    </div>
                <?php else: ?>
                    <?php
                    foreach ($data as $row) {
                        $chart_dates[] = $row['date'];
                        $chart_opens[] = floatval($row['open']);
                        $chart_highs[] = floatval($row['high']);
                        $chart_lows[] = floatval($row['low']);
                        $chart_closes[] = floatval($row['close']);
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
                                    tickprefix: "$"
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
