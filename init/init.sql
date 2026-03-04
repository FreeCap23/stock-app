CREATE TABLE IF NOT EXISTS ticker_metadata (
    symbol          VARCHAR(6) NOT NULL,
    name            VARCHAR(64) NOT NULL,
    description     TEXT,
    start_date      DATE NOT NULL,
    end_date        DATE,
    exchange_code   VARCHAR(32) NOT NULL,
    PRIMARY KEY     (symbol)
);

CREATE TABLE IF NOT EXISTS daily_ohlcv (
    symbol  CHAR(6) NOT NULL,
    date    DATE NOT NULL,
    open    DECIMAL(10, 3) NOT NULL,
    high    DECIMAL(10, 3) NOT NULL,
    low     DECIMAL(10, 3) NOT NULL,
    close   DECIMAL(10, 3) NOT NULL,
    volume  INT UNSIGNED NOT NULL,
    PRIMARY KEY (symbol, date)-- ,
    -- TODO: uncomment this line and recreate the mariadb volume after you implement saving the metadata of the tickers
    -- If the symbol is a foreign key but the symbol doesn't exist in the metadata table, then the db doesn't let us create an entry here because it's missing the parent
    -- FOREIGN KEY(symbol) REFERENCES ticker_metadata(symbol)
);

CREATE TABLE IF NOT EXISTS users (
    id              INTEGER AUTO_INCREMENT PRIMARY KEY,
    username        VARCHAR(32) NOT NULL UNIQUE,
    password        VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    date_registered DATE NOT NULL
);
