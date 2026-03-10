# Stock App

A PHP-based web application for visualizing historical stock market data (OHLCV - Open, High, Low, Close, Volume) with user authentication and persistent data storage.

## Overview

Stock App provides a clean, intuitive interface for analyzing stock price movements over time. The application fetches stock data from the Tiingo API, caches it in a MariaDB database, and renders interactive candlestick charts using Plotly.js.

## Features

- **User Authentication**: Secure login and registration system with session management
- **Stock Data Visualization**: Interactive candlestick charts powered by Plotly.js
- **Intelligent Data Caching**: Automatically detects gaps in historical data and fetches missing periods from Tiingo API
- **Date Range Selection**: Query stocks for custom date ranges with validation
- **Persistent Storage**: MariaDB backend for efficient data management
- **Responsive UI**: Mobile-friendly interface with clean, modern styling

## Technology Stack

### Backend
- **Language**: PHP 8+ (with strict type declarations)
- **Database**: MariaDB
- **API Provider**: Tiingo (stock market data)
- **Data Access**: PDO with prepared statements

### Frontend
- **HTML5**
- **CSS3**
- **JavaScript**: Plotly.js for interactive charting

### Infrastructure
- **Containerization**: Docker & Docker Compose
- **Code Quality**: PHP-CS-Fixer for consistent code formatting
- **Testing**: PHPUnit (configured in composer.json)

## Prerequisites

- Docker 20.10+
- Docker Compose 1.29+
- Tiingo API key (free tier: https://www.tiingo.com)

## Quick Start

### 1. Clone the repository
```bash
git clone https://github.com/FreeCap23/stock-app.git
cd stock-app
```

### 2. Run Make to setup everything
`make`
This will ask for a Tiingo API key, then will build the container and run it.

### 3. Access the application
- Web UI: http://localhost:8080
- PhpMyAdmin: http://localhost:8081

## Usage

### View Stock Charts
1. Enter a stock symbol (e.g., `AAPL`)
2. Select start and end dates
3. Click "Load Chart"
4. The app fetches missing data from Tiingo and displays an interactive candlestick chart

## Development

### Running Tests
```bash
make test      # Run PHPUnit tests
```

## Database Schema

### users table
```sql
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL
);
```

### daily_ohlcv table
```sql
CREATE TABLE daily_ohlcv (
  id INT AUTO_INCREMENT PRIMARY KEY,
  symbol VARCHAR(10) NOT NULL,
  date DATE NOT NULL,
  open DECIMAL(10, 4),
  high DECIMAL(10, 4),
  low DECIMAL(10, 4),
  close DECIMAL(10, 4),
  volume BIGINT,
  UNIQUE KEY unique_symbol_date (symbol, date)
);
```

## API Integration

### Tiingo
Stock data is fetched from Tiingo's daily OHLCV endpoint:
```
GET https://api.tiingo.com/tiingo/daily/{ticker}/prices
```

**Key Features**:
- Parameterized requests
- Automatic caching to reduce API calls
- Exception handling for network failures
- Custom date range support

## Security

### Authentication
- Passwords hashed with `password_hash()`
- Session regeneration after login
- Server-side session validation

### Input Protection
- All user inputs sanitized with `htmlspecialchars()`
- Prepared statements prevent SQL injection
- Date inputs validated with `DateTime::createFromFormat()`

## Support

For issues or questions, please open an issue on GitHub.

## Acknowledgments

- [Tiingo](https://www.tiingo.com) — stock market data provider
- [Plotly.js](https://plotly.com/javascript/) — interactive charting library
- [PHP-CS-Fixer](https://cs.symfony.com/) — code quality automation
