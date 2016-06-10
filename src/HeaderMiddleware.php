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
 * Middleware to add headers to the response.
 *
 * Allows arbitrary headers to be added to to each response.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class HeaderMiddleware extends \Slim\Middleware {

	/**
	 * @var array
	 */
	protected $headers;

	/**
	 * @param array $headers Collection of headers to set. Array keys are header
	 *   names and values are either header values or callables that will return
	 *   header values. Callables will be passed the current Slim application as
	 *   the sole argument and must return either a string value to use as the
	 *   header content or null to cancel setting this header.
	 */
	public function __construct( array $headers = array() ) {
		$this->headers = $headers;
	}

	public function call() {
		$resp = $this->app->response;
		foreach ( $this->headers as $name => $value ) {
			if ( is_callable( $value ) ) {
				$value = $value( $this->app );
			}

			if ( $value !== null ) {
				$resp->header( $name, $value );
			}
		}

		$this->next->call();
	}

}
