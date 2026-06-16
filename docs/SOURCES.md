# Source Configuration

External data sources are configured in:

```text
config/sources.php
```

The file returns a PHP array. Each top-level key is a source identifier.

Example:

```php
return [
    'ngs' => [
        // source settings
    ],

    'lenta' => [
        // source settings
    ],
];
```

The source key is used in the URL:

```text
/?source=ngs&page=1
/?source=lenta&page=1
```

## Source structure

Each source contains several main blocks:

```php
'source_key' => [
    'name' => 'Source display name',
    'type' => 'html',
    'url' => 'First page URL',
    'base_url' => 'Base website URL',
    'is_active' => true,

    'request' => [
        // HTTP request settings
    ],

    'pagination' => [
        // Pagination settings
    ],

    'list' => [
        // List page parsing settings
    ],

    'detail' => [
        // Detail page parsing settings
    ],
],
```

## Main parameters

### `name`

Display name of the source in the web interface.

```php
'name' => 'NGS Latest News',
```

### `type`

Expected response type. Current sources use HTML.

```php
'type' => 'html',
```

### `url`

First page URL.

```php
'url' => 'https://ngs.ru/text/',
```

### `base_url`

Base URL for converting relative links into absolute links.

```php
'base_url' => 'https://ngs.ru',
```

Example:

```text
/text/example/
```

becomes:

```text
https://ngs.ru/text/example/
```

### `is_active`

Controls whether the source is visible in the interface.

```php
'is_active' => true,
```

Use `false` to disable a source without deleting it.

## Request settings

The `request` block controls HTTP request options.

```php
'request' => [
    'user_agent' => 'Mozilla/5.0 ...',
    'headers' => [
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
    ],
    'referer' => 'https://ngs.ru/text/',
    'use_cookie_jar' => false,
],
```

### `user_agent`

Browser-like user agent. Some websites may block requests without it.

### `headers`

Additional HTTP headers.

### `referer`

HTTP referer header.

### `use_cookie_jar`

Enables cookie storage for the source.

Use `false` for simple public pages.

Use `true` only if the website requires cookies between requests.

## Pagination settings

The `pagination` block describes how source pages are loaded.

```php
'pagination' => [
    'enabled' => true,
    'url_template' => 'https://ngs.ru/text/page-{page}/',
    'start_page' => 1,
    'app_items_per_page' => 10,
    'max_source_pages_per_request' => 3,
    'auto_calibrate' => true,
    'calibration_ttl' => 3600,
],
```

### `enabled`

Enables pagination.

### `url_template`

Template for source page URLs.

The `{page}` placeholder is replaced with the real page number.

Example:

```php
'url_template' => 'https://ngs.ru/text/page-{page}/',
```

For page 2:

```text
https://ngs.ru/text/page-2/
```

### `start_page`

First page number used by the source.

Usually:

```php
'start_page' => 1,
```

### `app_items_per_page`

Number of items displayed on one application page.

```php
'app_items_per_page' => 10,
```

### `max_source_pages_per_request`

Maximum number of external pages loaded during one application request.

This protects the application from sending too many requests.

### `auto_calibrate`

Enables automatic detection of item count on source pages.

### `calibration_ttl`

Pagination calibration cache lifetime in seconds.

```php
'calibration_ttl' => 3600,
```

## List parsing

The `list` block describes how to extract items from a source list page.

```php
'list' => [
    'item' => [
        'selector_type' => 'class',
        'selector' => 'wrap_RL97A',
    ],

    'fields' => [
        // item fields
    ],
],
```

### `item`

Defines one repeated item container on the list page.

The parser first finds all item containers, then extracts fields from each item.

### `selector_type`

Supported selector types:

```php
'class'
'id'
'tag'
```

### `selector`

The actual class, ID, or tag name.

Example:

```php
'selector' => 'wrap_RL97A',
```

## Field parsing

Fields describe what should be extracted from each item.

Example:

```php
'title' => [
    'type' => 'text',
    'selector_type' => 'class',
    'selector' => 'header_RL97A',
    'attribute' => 'text',
    'required' => true,
],
```

### Field key

The array key becomes the output field name.

Example:

```php
'title'
'detail_url'
'summary'
'published_at'
'image'
'category'
```

### `type`

Logical field type.

Common values:

```php
'text'
'link'
'date'
'image'
```

### `attribute`

Defines what should be extracted.

Use `text` for visible text:

```php
'attribute' => 'text',
```

Use `href` for links:

```php
'attribute' => 'href',
```

Use `src` for images:

```php
'attribute' => 'src',
```

### `required`

If `true`, the item is skipped when this field is empty.

Usually required:

```php
'title'
'detail_url'
```

Usually optional:

```php
'summary'
'published_at'
'image'
'category'
```

### `resolve_url`

Converts relative URLs into absolute URLs using `base_url`.

Use it for links and images.

```php
'resolve_url' => true,
```

## Detail parsing

The `detail` block describes how to parse a selected item page.

```php
'detail' => [
    'enabled' => true,
    'url_from_field' => 'detail_url',

    'fields' => [
        // detail fields
    ],
],
```

### `enabled`

Enables detail page loading.

### `url_from_field`

Defines which list field contains the detail page URL.

Usually:

```php
'url_from_field' => 'detail_url',
```

### Detail fields

Detail fields work the same way as list fields.

Example:

```php
'title' => [
    'type' => 'text',
    'selector_type' => 'tag',
    'selector' => 'h1',
    'attribute' => 'text',
    'required' => true,
],
```

## Multiple values

Use `multiple` when several elements should be collected.

Example:

```php
'content' => [
    'type' => 'text',
    'selector_type' => 'tag',
    'selector' => 'p',
    'attribute' => 'text',
    'multiple' => true,
    'separator' => "\n\n",
    'required' => false,
],
```

### `multiple`

Collects all matching elements.

### `separator`

Joins extracted values.

## How to add a new source

1. Open:

```text
config/sources.php
```

2. Add a new source key:

```php
'example' => [
    // source configuration
],
```

3. Set basic source data:

```php
'name' => 'Example News',
'type' => 'html',
'url' => 'https://example.com/news/',
'base_url' => 'https://example.com',
'is_active' => true,
```

4. Add request settings.

Usually it is enough to copy the `request` block from an existing source and update `referer`.

5. Configure pagination.

Example:

```php
'pagination' => [
    'enabled' => true,
    'url_template' => 'https://example.com/news/page-{page}/',
    'start_page' => 1,
    'app_items_per_page' => 10,
    'max_source_pages_per_request' => 3,
    'auto_calibrate' => true,
    'calibration_ttl' => 3600,
],
```

6. Open the source website in a browser and inspect HTML.

7. Find the repeated item container.

Example:

```html
<div class="news-card">
```

8. Add it to `list.item`:

```php
'item' => [
    'selector_type' => 'class',
    'selector' => 'news-card',
],
```

9. Find title, link, date, category, and image elements inside the item.

10. Add them to `list.fields`.

11. Open one detail page and inspect its HTML.

12. Add detail page selectors to `detail.fields`.

13. Test the source in the browser:

```text
/?source=example&page=1
```

## Minimal source example

```php
'example' => [
    'name' => 'Example News',
    'type' => 'html',
    'url' => 'https://example.com/news/',
    'base_url' => 'https://example.com',
    'is_active' => true,

    'request' => [
        'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:120.0) Gecko/20100101 Firefox/120.0',
        'headers' => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9',
        ],
        'referer' => 'https://example.com/news/',
        'use_cookie_jar' => false,
    ],

    'pagination' => [
        'enabled' => true,
        'url_template' => 'https://example.com/news/page-{page}/',
        'start_page' => 1,
        'app_items_per_page' => 10,
        'max_source_pages_per_request' => 3,
        'auto_calibrate' => true,
        'calibration_ttl' => 3600,
    ],

    'list' => [
        'item' => [
            'selector_type' => 'class',
            'selector' => 'news-card',
        ],

        'fields' => [
            'title' => [
                'type' => 'text',
                'selector_type' => 'class',
                'selector' => 'news-card__title',
                'attribute' => 'text',
                'required' => true,
            ],

            'detail_url' => [
                'type' => 'link',
                'purpose' => 'detail_page',
                'selector_type' => 'class',
                'selector' => 'news-card__link',
                'attribute' => 'href',
                'resolve_url' => true,
                'required' => true,
            ],
        ],
    ],

    'detail' => [
        'enabled' => true,
        'url_from_field' => 'detail_url',

        'fields' => [
            'title' => [
                'type' => 'text',
                'selector_type' => 'tag',
                'selector' => 'h1',
                'attribute' => 'text',
                'required' => true,
            ],

            'content' => [
                'type' => 'text',
                'selector_type' => 'tag',
                'selector' => 'p',
                'attribute' => 'text',
                'multiple' => true,
                'separator' => "\n\n",
                'required' => false,
            ],
        ],
    ],
],
```

## Notes

External websites can change their HTML structure.

If parsing stops working, selectors in `config/sources.php` usually need to be updated.

For similar HTML sources, the main application code does not need to be changed.
