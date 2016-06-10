<?php
/**
 * @section LICENSE
 * This file is part of Wikimedia Slim application library
 *
 * Wikimedia Slim application library is free software: you can
 * redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * Wikimedia Slim application library is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with Wikimedia Grants Review application.  If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * @file
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */

namespace Wikimedia\Slimapp;

/**
 * Grants review application.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
abstract class AbstractApp {

	/**
	 * @var string $deployDir
	 */
	protected $deployDir;

	/**
	 * @var \Slim\Slim $slim
	 */
	protected $slim;

	/**
	 * @param string $deployDir Full path to code deployment
	 * @param array $settings Associative array of application settings
	 */
	public function __construct( $deployDir, array $settings = array() ) {
		$this->deployDir = $deployDir;

		$this->slim = new \Slim\Slim( array_merge(
			array(
				'mode' => 'production',
				'debug' => false,
				'log.channel' => Config::getStr( 'LOG_CHANNEL', 'app' ),
				'log.level' => Config::getStr(
					'LOG_LEVEL', \Psr\Log\LogLevel::NOTICE
				),
				'log.file' => Config::getStr( 'LOG_FILE', 'php://stderr' ),
				'view' => new \Slim\Views\Twig(),
				'view.cache' => Config::getStr(
					'CACHE_DIR', "{$this->deployDir}/data/cache"
				),
				'templates.path' => Config::getStr(
					'TEMPLATE_DIR', "{$this->deployDir}/data/templates"
				),
				'i18n.path' => Config::getStr(
					'I18N_DIR', "{$this->deployDir}/data/i18n"
				),
				'i18n.default' => Config::getstr( 'DEFAULT_LANG', 'en' ),
				'smtp.host' => Config::getStr( 'SMTP_HOST', 'localhost' ),
			),
			$settings
		) );

		// Slim does not natively understand being behind a proxy. If not
		// corrected template links created via siteUrl() may use the wrong
		// Protocol (http instead of https).
		if ( getenv( 'HTTP_X_FORWARDED_PROTO' ) ) {
			$proto = getenv( 'HTTP_X_FORWARDED_PROTO' );
			$this->slim->environment['slim.url_scheme'] = $proto;

			$port = getenv( 'HTTP_X_FORWARDED_PORT' );
			if ( $port === false ) {
				$port = ( $proto == 'https' ) ? '443' : '80';
			}
			$this->slim->environment['SERVER_PORT'] = $port;
		}

		$this->configureSlim( $this->slim );

		// Replace default logger with monolog.
		// Done before configureIoc() so subclasses can easily switch it again
		// if desired.
		$this->slim->container->singleton( 'log', function ( $c ) {
			// Convert string level to Monolog integer value
			$level = strtoupper( $c->settings['log.level'] );
			$level = constant( "\Monolog\Logger::{$level}" );

			$log = new \Monolog\Logger( $c->settings['log.channel'] );
			$handler = new \Monolog\Handler\Udp2logHandler(
				$c->settings['log.file'],
				$level
			);
			$handler->setFormatter( new \Monolog\Formatter\LogstashFormatter(
				$c->settings['log.channel'], null, null, '',
				\Monolog\Formatter\LogstashFormatter::V1
			) );
			$handler->pushProcessor(
				new \Monolog\Processor\PsrLogMessageProcessor()
			);
			$handler->pushProcessor(
				new \Monolog\Processor\ProcessIdProcessor()
			);
			$handler->pushProcessor( new \Monolog\Processor\UidProcessor() );
			$handler->pushProcessor( new \Monolog\Processor\WebProcessor() );
			$log->pushHandler( $handler );
			return $log;
		} );

		$this->configureIoc( $this->slim->container );
		$this->configureView( $this->slim->view );

		$this->slim->add(
			new HeaderMiddleware( $this->configureHeaderMiddleware() )
		);

		// Add CSRF protection for POST requests
		$this->slim->add( new CsrfMiddleware() );

		$this->configureRoutes( $this->slim );
	}

	/**
	 * Apply settings to the Slim application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	abstract protected function configureSlim( \Slim\Slim $slim );

	/**
	 * Configure inversion of control/dependency injection container.
	 *
	 * @param \Slim\Helper\Set $container IOC container
	 */
	abstract protected function configureIoc( \Slim\Helper\Set $container );

	/**
	 * Configure view behavior.
	 *
	 * @param \Slim\View $view Default view
	 */
	abstract protected function configureView( \Slim\View $view );

	/**
	 * Configure routes to be handled by application.
	 *
	 * @param \Slim\Slim $slim Application
	 */
	abstract protected function configureRoutes( \Slim\Slim $slim );

	/**
	 * Main entry point for all requests.
	 */
	public function run() {
		session_name( '_s' );
		session_cache_limiter( false );
		ini_set( 'session.cookie_httponly', true );
		session_start();
		register_shutdown_function( 'session_write_close' );
		$this->slim->run();
	}

	/**
	 * Add a redirect route to the app.
	 * @param \Slim\Slim $slim App
	 * @param string $name Page name
	 * @param string $to Redirect target route name
	 * @param string $routeName Name for the route
	 */
	public static function redirect(
		\Slim\Slim $slim, $name, $to, $routeName = null
	) {
		$routeName = $routeName ?: $name;

		$slim->get( $name, function () use ( $slim, $name, $to ) {
			$slim->flashKeep();
			$slim->redirect( $slim->urlFor( $to ) );
		} )->name( $routeName );
	}

	/**
	 * Add a static template route to the app.
	 * @param \Slim\Slim $slim App
	 * @param string $name Page name
	 * @param string $routeName Name for the route
	 */
	public static function template(
		\Slim\Slim $slim, $name, $routeName = null
	) {
		$routeName = $routeName ?: $name;

		$slim->get( $name, function () use ( $slim, $name ) {
			$slim->render( "{$name}.html" );
		} )->name( $routeName );
	}

	/**
	 * Configure the default HeaderMiddleware installed for all routes.
	 *
	 * Default configuration adds these headers:
	 * - "Vary: Cookie" to help upstream caches to the right thing
	 * - "X-Frame-Options: DENY"
	 * - A fairly strict 'self' only Content-Security-Policy to help protect
	 *   against XSS attacks
	 * - "Content-Type: text/html; charset=UTF-8"
	 *
	 * @return array
	 */
	protected function configureHeaderMiddleware() {
		// Add headers to all responses:
		return array(
			'Vary' => 'Cookie',
			'X-Frame-Options' => 'DENY',
			'Content-Security-Policy' =>
				"default-src 'self'; " .
				"frame-src 'none'; " .
				"object-src 'none'; " .
				// Needed for css data:... sprites
				"img-src 'self' data:; " .
				// Needed for jQuery and Modernizr feature detection
				"style-src 'self' 'unsafe-inline'",
			// Don't forget to override this for any content that is not
			// actually HTML (e.g. json)
			'Content-Type' => 'text/html; charset=UTF-8',
		);
	}
}
