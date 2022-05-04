Wikimedia SlimApp
=================

Common classes to help with creating an application using the
[Slim](https://www.slimframework.com/) micro framework and
[Twig](https://twig.symfony.com/) template engine.

System Requirements
-------------------
* PHP >= 7.2.0

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

Working on the code
-------------------
Code review process is done through [Gerrit](https://gerrit.wikimedia.org/).
To start hacking on the application refer to the [Gerrit
Tutorial](https://www.mediawiki.org/wiki/Gerrit/Tutorial).

Key Features
------------
#### Dao
Base Class for data access objects

This class contains common methods for performing SQL operations and handling
nested transactions.

#### Controller
Page Controller

This class contains common methods for setting default data, getting flash
messages and handling undefined methods.

#### Form
Class for collecting and validating users' data

This class contains common methods for getting users' data, validating it and
getting error messages in case of invalid data.

Authors
-------
* Bryan Davis, Wikimedia Foundation
* Niharika Kohli, Wikimedia Foundation

Based on code developed for the Wikimania Scholarships application and the
Wikimedia Grants Review application.

License
-------
[GNU GPL 3.0+](//www.gnu.org/copyleft/gpl.html "GNU GPL 3.0")
