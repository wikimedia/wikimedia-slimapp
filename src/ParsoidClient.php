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

use Psr\Log\LoggerInterface;

/**
 * Simple client for sending wikitext to parsoid to be converted into html.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 * @see https://www.mediawiki.org/wiki/Parsoid/API
 */
class ParsoidClient {

	/**
	 * @var string $url
	 */
	protected $url;

	/**
	 * @var string $cache
	 */
	protected $cache;

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;


	/**
	 * @param string $url URL to parsoid API
	 * @param string $cache Cache directory
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $url, $cache, $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
		$this->url = $url;
		$this->cache = $cache;
	}

	public function parse( $text ) {
		$this->logger->debug( 'Parsing [{text}]', array(
			'method' => __METHOD__,
			'text' => $text,
		) );
		$key = sha1( $text );
		$parsed = $this->cacheGet( $key );
		if ( $parsed === null ) {
			$parsed = $this->fetchParse( $text );
			if ( $parsed === false ) {
				// return raw text if fetch fails
				$parsed = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
			} else {
				$this->cachePut( $key, $parsed );
			}
		}
		return $parsed;
	}

	protected function cacheGet( $key ) {
		$file = "{$this->cache}/{$key}.parsoid";
		if ( file_exists( $file ) ) {
			$this->logger->debug( 'Cache hit for [{key}]', array(
				'method' => __METHOD__,
				'key' => $key,
			) );
			return file_get_contents( $file );
		}
		$this->logger->info( 'Cache miss for [{key}]', array(
			'method' => __METHOD__,
			'key' => $key,
		) );
		return null;
	}

	protected function cachePut( $key, $value ) {
		$file = "{$this->cache}/{$key}.parsoid";
		file_put_contents( $file, $value );
		$this->logger->info( 'Cache put for [{key}]', array(
			'method' => __METHOD__,
			'key' => $key,
			'file' => $file,
			'value' => $value,
		) );
	}

	/**
	 * @param string $text
	 * @return string|bool False on failure, html otherwise
	 */
	protected function fetchParse( $text ) {
		$parms = array(
			'wt' => $text,
			'body' => 1,
		);
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $this->url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $parms );
		curl_setopt( $ch, CURLOPT_ENCODING, '' );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'IEG Grant review 0.1' );
		$stderr = fopen( 'php://temp', 'rw+' );
		curl_setopt( $ch, CURLOPT_VERBOSE, true );
		curl_setopt( $ch, CURLOPT_STDERR, $stderr );
		$body = curl_exec( $ch );
		rewind( $stderr );
		$this->logger->debug( 'Parsoid curl request', array(
			'method' => __METHOD__,
			'url' => $this->url,
			'parms' => $parms,
			'stderr' => stream_get_contents( $stderr ),
		) );
		if ( $body === false ) {
			$this->logger->error( 'Curl error #{errno}: {error}', array(
				'method' => __METHOD__,
				'errno' => curl_errno( $ch ),
				'error' => curl_error( $ch ),
				'url' => $this->url,
				'parms' => $parms,
			) );
			curl_close( $ch );
			return false;
		}
		curl_close( $ch );

		// Using a regex to parse html is generally not a sane thing to do,
		// but in this case we are trusting Parsoid to be returning clean HTML
		// and all we want to do is unwrap our payload from the
		// <body>...</body> tag.
		return preg_replace( '@^.*<body[^>]+>(.*)</body>.*$@s', '$1', $body );
	}
}
