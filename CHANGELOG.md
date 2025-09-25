# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog, and this project adheres to Semantic Versioning where possible.

## [Unreleased]

## [2025-09-24]
### Added
- Amadeus Self-Service integration in `index.php`:
  - `amadeus_get_credentials()`, `amadeus_get_token()`, `amadeus_search_flights()` to query Amadeus APIs.
  - Token caching in session: `$_SESSION['amadeus_token']` and `$_SESSION['amadeus_token_expires']`.
  - `map_amadeus_offers_to_flights()` to adapt API responses to the UI flight card structure.
  - `iso8601_duration_to_text()` to render human-friendly durations.
- Environment configuration:
  - `.env` loader via `load_env(__DIR__ . '/.env')` in `index.php` and `locations.php`.
  - `AMADEUS_BASE_URL` resolved from environment with fallback to `https://test.api.amadeus.com`.
  - Credentials read from `AMADEUS_API_KEY` and `AMADEUS_API_SECRET`.
- Flight search form wiring in `index.php`:
  - POST handler for `flight_search` validates inputs and loads live flight results.
  - Preserves static `$flights` only when no live results are present.
- Autocomplete for origin/destination in `index.php`:
  - `<datalist id="airports-list">` with base options.
  - Defaults: `LAX` as origin and `GDL` as destination.
  - JS normalizer to enforce 3-letter IATA codes on submit.
  - Dynamic autocomplete backed by new `locations.php` proxy (debounced fetch).
- Passengers field:
  - Numeric `personas` (1–9) added to the form and passed as `adults` to Amadeus.
- One-way vs round-trip toggle:
  - Radio buttons to switch between `round_trip` and `one_way`.
  - Return date hidden/disabled on one-way; backend ignores `fechaVuelta` accordingly.
- Flight type persistence:
  - Last selected type stored in `$_SESSION['last_flight_type']`.
  - Hero radios preselected from session.
  - Filters in Flights section default to last selection if GET param is absent.
- Pagination for Flights section:
  - Server-side pagination limiting to 5 flight cards per page.
  - Preserves current filters in pagination URLs.
  - Adds previous/next and numeric page links anchoring to `#flights`.

### Changed
- Form actions now use `htmlspecialchars($_SERVER['PHP_SELF'])` for safety.
- Avoid overwriting live results with static demo data by guarding `$flights` defaults.

### Fixed
- Balanced braces and removed stray `}` causing parse errors in `index.php`.
- Added defensive checks and friendly errors when PHP cURL extension is missing.

### Notes
- Ensure PHP cURL extension is installed and enabled (e.g., `php8.3-curl`).
- `.env` should not be committed to public repos; consider adding it to `.gitignore`.
