CREATE TABLE IF NOT EXISTS daily_ohlcv (
    symbol  CHAR(6) NOT NULL,
    date    DATE NOT NULL,
    open    DECIMAL(10, 3) NOT NULL,
    high    DECIMAL(10, 3) NOT NULL,
    low     DECIMAL(10, 3) NOT NULL,
    close   DECIMAL(10, 3) NOT NULL,
    volume  INT UNSIGNED NOT NULL,
    PRIMARY KEY (symbol, date)
);
