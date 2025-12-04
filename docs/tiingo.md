# Tiingo

## Pricing
For individuals, the free plan provides:
- 30 years of historical data
- 500 Unique symbols per Month
- 50 requests / hour
- 1000 requests / day
- 1 GB bandwidth / month

---

## REST endpoint End-Of-Day
Tiingo's REST endpoint can provide **JSON** and **CSV** formatted data. The **CSV** data is ~2x smaller than the **JSON** data.

### Example output
[JSON Daily OHLCV between Jan 1st 2012 & Jan 1st 2016](https://api.tiingo.com/tiingo/daily/AAPL/prices?token=82a1127ac6f54383240d583d22763e87647fb58a&startDate=2012-1-1&endDate=2016-1-1) 260 KB

[JSON Daily OHLCV between Jan 1st 2012 & Present](https://api.tiingo.com/tiingo/daily/AAPL/prices?token=82a1127ac6f54383240d583d22763e87647fb58a&startDate=2012-1-1) 911 KB

[CSV Daily OHLCV between Jan 1st 2012 & Jan 1st 2016](https://api.tiingo.com/tiingo/daily/AAPL/prices?token=82a1127ac6f54383240d583d22763e87647fb58a&startDate=2012-1-1&endDate=2016-1-1&format=csv) 134 KB

[CSV Daily OHLCV between Jan 1st 2012 & Present](https://api.tiingo.com/tiingo/daily/AAPL/prices?token=82a1127ac6f54383240d583d22763e87647fb58a&startDate=2012-1-1&format=csv&format=csv) 464 KB

------

## REST endpoint Meta
This endpoint provides information about a ticker such as:
- Company name
- Company description
- Earliest price date
- Latest price date
- Exchange code
