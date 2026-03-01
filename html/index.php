<?php

declare(strict_types=1);

namespace stock_app;

require __DIR__ . "/vendor/autoload.php";

use InvalidArgumentException;
use RuntimeException;
use DateInterval;
use DateTime;
use Exception;

$error_message = null;
$start_date = isset($_GET["start_date"])
    ? htmlspecialchars($_GET["start_date"])
    : "";
$end_date = isset($_GET["end_date"]) ? htmlspecialchars($_GET["end_date"]) : "";
$symbol = isset($_GET["symbol"]) ? htmlspecialchars($_GET["symbol"]) : "";

$tiingo = new Tiingo();
if (array_key_exists("TIINGO_API_TOKEN", $_ENV)) {
    $tiingo->setApiKey($_ENV["TIINGO_API_TOKEN"]);
} else {
    throw new RuntimeException(
        "Tiingo API key environment variable not found!",
    );
}

$db = new MariaDB();
if (
    array_key_exists("MARIADB_USER", $_ENV) &&
    array_key_exists("MARIADB_PASSWORD", $_ENV) &&
    array_key_exists("MARIADB_DATABASE", $_ENV)
) {
    $db->initConnection(
        "db",
        $_ENV["MARIADB_DATABASE"],
        $_ENV["MARIADB_USER"],
        $_ENV["MARIADB_PASSWORD"],
    );
} else {
    throw new RuntimeException("MariaDB environment variables not found!");
}

if ($symbol && $start_date && $end_date) {
    try {
        $start = DateTime::createFromFormat("Y-m-d", $start_date);
        $end = DateTime::createFromFormat("Y-m-d", $end_date);

        $existing_dates = $db->getExistingDates(
            $symbol,
            $start_date,
            $end_date,
        );

        $missing_ranges = [];
        $current_missing_start = null;
        $iterator_date = clone $start;

        while ($iterator_date <= $end) {
            $date_str = $iterator_date->format("Y-m-d");

            // (1 = Mon ... 6 = Sat, 7 = Sun)
            $is_weekend = in_array($iterator_date->format("N"), [6, 7]);

            if (!in_array($date_str, $existing_dates) && !$is_weekend) {
                if ($current_missing_start === null) {
                    $current_missing_start = clone $iterator_date;
                }
            } else {
                // Date exists (or is weekend). If we were tracking a gap, close it and save it.
                if ($current_missing_start !== null) {
                    $missing_ranges[] = [
                        "start" => $current_missing_start,
                        "end" => (clone $iterator_date)->modify("-1 day"),
                    ];
                    $current_missing_start = null;
                }
            }
            $iterator_date->modify("+1 day");
        }

        // Close any trailing gap that reaches the very end of our requested range
        if ($current_missing_start !== null) {
            $missing_ranges[] = [
                "start" => $current_missing_start,
                "end" => clone $end,
            ];
        }

        foreach ($missing_ranges as $range) {
            $ohlcv_array = $tiingo->getOhlcv(
                $symbol,
                $range["start"],
                $range["end"],
            );

            if (!empty($ohlcv_array)) {
                $db->ingestOhlcvArray($ohlcv_array, $symbol);
            }
        }

        $final_data = $db->fetchOhlcvData($symbol, $start_date, $end_date);
    } catch (TiingoException | InvalidArgumentException | Exception $e) {
        $error_message = $e->getMessage();
        error_log($error_message);
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
                        max=<?php echo date_create()
                            ->sub(DateInterval::createFromDateString("1 day"))
                            ->format("Y-m-d"); ?>
                        <?php if ($start_date == ""): ?>
                            value=<?php echo date_create()
                                ->sub(
                                    DateInterval::createFromDateString("1 day"),
                                )
                                ->format("Y-m-d"); ?>
                        <?php else: ?>
                            value=<?php echo $start_date; ?>
                        <?php endif; ?>
                        required
                    >
                    <label for="end_date">End date: </label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        <?php if ($end_date == ""): ?>
                            value=<?php echo date_create()->format("Y-m-d"); ?>
                        <?php else: ?>
                            value=<?php echo $end_date; ?>
                        <?php endif; ?>
                        required
                    >
                    <input type="submit" value="Load Chart">
                </form>
            </div>

            <?php if ($symbol): ?>
                <?php if ($error_message): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php else: ?>
                    <?php
                    $chart_dates = [];
                    $chart_opens = [];
                    $chart_highs = [];
                    $chart_lows = [];
                    $chart_closes = [];
                    foreach ($final_data as $ohlcv) {
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
                                x: <?php echo json_encode(
                                    $chart_dates,
                                ); ?>.map(date => new Date(date)),
                                open: <?php echo json_encode($chart_opens); ?>,
                                high: <?php echo json_encode($chart_highs); ?>,
                                low: <?php echo json_encode($chart_lows); ?>,
                                close: <?php echo json_encode(
                                    $chart_closes,
                                ); ?>,
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
                                    title: {
                                        text: "Price (USD)",
                                        standoff: 20
                                    },
                                    tickprefix: "$",
                                    side: "right",
                                    automargin: true
                                },
                                margin: { t: 50, r: 30, b: 50, l: 60 },
                                shapes: [
                                // Horizontal line at last close price
                                {
                                    type: 'line',
                                    xref: 'paper',
                                    yref: 'y',

                                    // These values don't matter
                                    x0: 0,
                                    x1: 1,

                                    y0: <?php echo (float) end(
                                        $chart_closes,
                                    ); ?>,
                                    y1: <?php echo (float) end(
                                        $chart_closes,
                                    ); ?>,
                                    line: {
                                        // If price at close > price at open, draw the line in green
                                        <?php if (
                                            (float) end($chart_closes) >
                                            (float) end($chart_opens)
                                        ): ?>
                                            color: '#2F7D32BA',
                                        // Otherwise, draw the line in red
                                        <?php else: ?>
                                            color: '#C62828BA',
                                        <?php endif; ?>
                                        width: 1,
                                        dash:'dot'
                                    }
                                }
                                ],
                                annotations: [
                                {
                                    xref: 'paper', // x takes values between 0 and 1
                                    x: 1, // Far right of the chart
                                    y: <?php echo (float) end(
                                        $chart_closes,
                                    ); ?>,

                                    xanchor: 'left',
                                    yanchor: 'middle',

                                    text: '<?php echo end($chart_closes); ?>',
                                    showarrow: false,

                                    font: {
                                        // If price at close > price at open, draw the text in green
                                        <?php if (
                                            (float) end($chart_closes) >
                                            (float) end($chart_opens)
                                        ): ?>
                                            color: '#2F7D32',
                                        // Otherwise, draw the text in red
                                        <?php else: ?>
                                            color: '#C62828',
                                        <?php endif; ?>
                                        size: 12,
                                    }
                                }
                                ]
                            };
                            Plotly.newPlot("chartContainer", [trace], layout, { responsive: true, scrollZoom: true });
                        }
                    </script>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </body>
</html>
