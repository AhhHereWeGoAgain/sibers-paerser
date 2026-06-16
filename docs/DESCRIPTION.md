# Project Description

## Purpose

This application is a PHP 8 web parser for loading and displaying data from external public sources.

The user can choose a data source, send a request, view parsed results with pagination, and open a detail page for a selected item.

The application was created without PHP frameworks. Most of the logic is implemented manually.

## Main features

* Data source selection
* External HTTP request sending
* HTML response parsing
* List page parsing
* Detail page parsing
* Pagination
* Error handling
* Safe HTML output escaping
* File-based cache for pagination metadata
* Easy extension through source configuration

## Database

The application does not use a database.

All data is loaded directly from external public sources and displayed in the web interface.

Temporary cache files may be stored in:

```text
storage/cache/
```

## Application flow

1. The user opens the web page.
2. The user selects a data source.
3. The controller receives GET parameters.
4. The service loads the selected external source.
5. The response type is detected.
6. The parser extracts configured fields from HTML.
7. Parsed items are passed to the view.
8. The view renders the result list with pagination.
9. If the user opens an item, the application loads and parses the detail page.

## Class model

### `DataController`

Handles incoming GET parameters and decides which page should be rendered.

Main responsibilities:

* Read selected source
* Read selected page
* Read detail URL
* Call the data service
* Pass data to views

### `DataFetchService`

Main application service for loading and parsing data.

Main responsibilities:

* Validate source configuration
* Build source URLs
* Send HTTP requests
* Detect response type
* Create the correct parser
* Parse list pages
* Parse detail pages
* Handle pagination
* Return normalized result arrays

### `HttpClient`

Responsible for external HTTP requests.

Main responsibilities:

* Send cURL requests
* Pass request headers
* Pass user agent
* Handle HTTP errors
* Return response body, content type and metadata

### `ResponseTypeDetector`

Detects response type by `Content-Type`.

Supported response types:

* HTML
* JSON
* XML
* RSS
* Unknown

Currently, the application mainly uses HTML parsing.

### `ParserFactory`

Creates parser instances based on detected response type.

Main responsibilities:

* Receive response type
* Return the correct parser
* Throw an error if parser is not available

### `ParserInterface`

Common interface for all parsers.

### `HtmlParser`

Parses HTML documents using source configuration.

Main responsibilities:

* Parse list pages
* Parse detail pages
* Extract text fields
* Extract links
* Extract images
* Resolve relative URLs
* Clean extracted text
* Validate parsed items

### `SourceRegistry`

Stores available data source configuration.

Main responsibilities:

* Return all active sources
* Check whether a source exists
* Return source configuration by key

## Source configuration

Data sources are configured in:

```text
config/sources.php
```

Each source contains:

* Source name
* Source URL
* Base URL
* Request headers
* Pagination settings
* List item selectors
* Detail page selectors

This approach allows adding new similar sources without changing the main controller or service logic.

## Views

Views are stored in:

src/View/

Main view files:

* `index.php`
* `detail.php`
* `header.php`
* `footer.php`
* `partials/source_form.php`
* `partials/items_list.php`
* `partials/pagination.php`
* `partials/error_card.php`

## Security notes

* User input is validated before use.
* Unknown source keys are rejected.
* HTML output is escaped before rendering.
* The web server should point only to the `public` directory.
* Internal directories such as `src`, `config` and `storage` must not be served directly.

## Extension

To add a new source:

1. Add a new source configuration in `config/sources.php`.
2. Configure list item selectors.
3. Configure detail page selectors if needed.
4. Test parsing result in the browser.


Field configuration instructions for `config/sources.php` are available in `SOURCES.md`.

No changes in controller logic are required for adding a similar HTML source.
