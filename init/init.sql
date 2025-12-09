CREATE TABLE IF NOT EXISTS daily_ohlcv (
    symbol  CHAR(6) NOT NULL,
    date    DATE NOT NULL,
    open    DECIMAL(10, 3) NOT NULL,
    high    DECIMAL(10, 3) NOT NULL,
    low     DECIMAL(10, 3) NOT NULL,
    close   DECIMAL(10, 3) NOT NULL,
    volume  INT UNSIGNED NOT NULL,
    PRIMARY KEY (symbol, date),
    FOREIGN KEY(symbol) REFERENCES ticker_metadata(symbol)
);

CREATE TABLE IF NOT EXISTS ticker_metadata (
    symbol          CHAR(6) NOT NULL,
    name            VARCHAR(64) NOT NULL,
    description     TEXT,
    start_date      DATE NOT NULL,
    end_date        DATE,
    exchange_code   VARCHAR(32) NOT NULL,
    PRIMARY KEY     (symbol)
)
