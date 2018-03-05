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
 * Middleware to manage Cross Site Request Forgery (CSRF) mitigation.
 *
 * Ensures that the user's session contains a random CSRF token. Verifies that
 * HTTP requests using POST, PUT and DELETE verbs provide a parameter that
 * matches the user's unique CSRF token. Exports 'csrf_param' and 'csrf_token'
 * values to the view that can be used to generate appropriate form inputs.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class CsrfMiddleware extends \Slim\Middleware {

	const PARAM = 'csrf_token';

	/**
	 * Handle CSRF validation and view injection.
	 */
	public function call() {
		if ( !isset( $_SESSION[self::PARAM] ) ) {
			$_SESSION[self::PARAM] = sha1( session_id() . microtime() );
		}

		$token = $_SESSION[self::PARAM];
		$method = $this->app->request()->getMethod();

		if ( in_array( $method, [ 'POST', 'PUT', 'DELETE' ] ) ) {
			$requestToken = $this->app->request()->post( self::PARAM );
			if ( $token !== $requestToken ) {
				$this->app->log->error( 'Missing or invalid CSRF token', [
					'got' => $requestToken,
					'expected' => $token,
				] );
				$this->app->render( 'csrf.html', [], 400 );
				return;
			}
		}

		$this->app->view()->replace( [
			'csrf_param' => self::PARAM,
			'csrf_token' => $token,
		] );

		$this->next->call();
	}

}
