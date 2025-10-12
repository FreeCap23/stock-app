<html>
    <head>
        <title>Stock App</title>
    </head>

    <body>

    <?php
    // Make a request to Alpha Vantage ========================================
    // Make sure the API key is present as an environment variable
    if (!array_key_exists("ALPHA_VANTAGE_API_KEY", $_ENV)) {
        die("API Key not found!");
    }
    $base_url = "https://www.alphavantage.co/query?";

    // Build parameter string
    $parameters = [
        "function" => "TIME_SERIES_DAILY",
        "symbol" => "AAPL",
        "outputsize" => "compact",
        "apikey" => $_ENV["ALPHA_VANTAGE_API_KEY"],
    ];

    // Build final GET Request url
    $url = $base_url . http_build_query($parameters);

    echo "<a href=$url>Click here</a>";
    ?>

    </body>
</html>
