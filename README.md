# External Data Parser

PHP 8 test task application for loading and parsing data from external public sources.

The application allows the user to select a data source, load parsed items, view results with pagination, and open item details.

## Main documentation

Additional project documentation is located in the `docs` directory.

It contains:

```text
docs/tretyakov-test-parser.example.conf
```

Example Apache virtual host configuration.

```text
docs/DESCRIPTION.md
```

Main functionality and class structure description.

Source configuration instructions for `config/sources.php` are available in:

```text
SOURCES.md
```

## Requirements

* Apache 2+
* PHP 8.x
* PHP Apache module
* PHP extensions:

  * curl
  * xml
  * mbstring
* Composer is optional because the `vendor` directory is already included.

For Ubuntu/Debian:

```bash
sudo apt update
sudo apt install apache2 php php-cli libapache2-mod-php php-curl php-xml php-mbstring
```

Enable Apache rewrite module:

```bash
sudo a2enmod rewrite
```

## Installation

Clone or copy the project to any local directory.

Go to the project directory:

```bash
cd path/to/project
```

The `vendor` directory is already included.

If `vendor` is removed, run:

```bash
composer install
composer dump-autoload
```

Create cache directory if it does not exist:

```bash
mkdir -p storage/cache
```

Set permissions for the storage directory:

```bash
sudo chown -R www-data:www-data storage
sudo chmod -R 775 storage
```

## Apache setup

The Apache virtual host must point to the `public` directory, not to the project root.

Copy the example configuration file from the project:

```bash
sudo cp docs/tretyakov-test-parser.example.conf /etc/apache2/sites-available/tretyakov-test-parser.conf
```

Open the copied Apache configuration file:

```bash
sudo nano /etc/apache2/sites-available/tretyakov-test-parser.conf
```

Inside this file, replace the example path with your own path to the project `public` directory.

Example:

```apache
DocumentRoot "/path/to/project/public"

<Directory "/path/to/project/public">
    Options -Indexes +FollowSymLinks
    AllowOverride All
    Require all granted
    DirectoryIndex index.php
</Directory>
```

Enable the site:

```bash
sudo a2ensite tretyakov-test-parser.conf
```

Check Apache configuration:

```bash
sudo apache2ctl configtest
```

If the result is:

```text
Syntax OK
```

restart Apache:

```bash
sudo systemctl restart apache2
```

## Local domain

If the virtual host uses:

```apache
ServerName sibers-parser.local
```

add this domain to `/etc/hosts`:

```bash
sudo nano /etc/hosts
```

Add:

```text
127.0.0.1 tretyakov-test-parser.local
```

Then open the application:

```text
http://tretyakov-test-parser.local
```

## Database

The application does not use a database.

No SQL dump is required.

## Usage

Open the application in a browser.

Select one of the available sources and click the load button.

Available sources:

* NGS Latest News
* Lenta.ru Latest News

Example URLs:

```text
http://sibers-parser.local/?source=ngs&page=1
http://sibers-parser.local/?source=lenta&page=1
```

## Notes

The application should be served only through the `public` directory.

Internal directories such as `src`, `config`, `storage`, and `docs` should not be available directly from the browser.
