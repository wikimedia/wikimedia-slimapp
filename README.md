Wikimedia SlimApp
=================

Common classes to help with creating an application using the Slim micro
framework and Twig template engine.

System Requirements
-------------------
* PHP >= 5.5.9

Configuration
-------------

The library follows the [Twelve-Factor App](http://12factor.net/)
configuration principle of configuration via environment variables.

The following variables can be optionally provided:

* LOG_CHANNEL = Logger name (default: `app`)
* LOG_LEVEL = PSR-3 logging level (default: `notice`)
* LOG_FILE = fopen()-compatible filename or stream URI (default: `php://stderr`)
* CACHE_DIR = Directory to cache twig templates (default: `data/cache`)
* SMTP_HOST = SMTP mail server (default: `localhost`)
* TEMPLATE_DIR = Twig template directory (default: `data/templates`)
* I18N_DIR = i18n data file directory (default: `data/i18n`)
* DEFAULT_LANG = Default i18n lanaguage (default: `en`)

### Apache

    SetEnv LOG_LEVEL debug
    SetEnv CACHE_DIR /var/cache/twig
    SetEnv DEFAULT_LANG es

### .env file

For environments where container based configuration isn't possible or
desired, a `.env` file can be placed in the root of the project. This file
will be parsed using PHP's `parse_ini_file()` function and the resulting
settings will be injected into the application environment.

    LOG_LEVEL=debug
    CACHE_DIR=/var/cache/twig
    DEFAULT_LANG=es

Authors
-------
* Bryan Davis, Wikimedia Foundation
* Niharika Kohli, Wikimedia Foundation

Based on code developed for the Wikimania Scholarships application and the
Wikimedia Grants Review application.

License
-------
[GNU GPL 3.0+](//www.gnu.org/copyleft/gpl.html "GNU GPL 3.0")
