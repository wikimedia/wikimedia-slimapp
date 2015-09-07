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

namespace Wikimedia\Slimapp\Auth;

/**
 * Manage authentication and authorization.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class AuthManager {

	const USER_SESSION_KEY = 'AUTH_USER';
	const NEXTPAGE_SESSION_KEY = 'AUTH_NEXTPAGE';

	/**
	 * @var UserManager $manager
	 */
	protected $manager;


	/**
	 * @param UserManager $manager
	 */
	public function __construct( UserManager $manager ) {
		$this->manager = $manager;
	}


	/**
	 * Get the current user's information
	 * @return UserData User information or null if not available
	 */
	public function getUserData() {
		if ( isset( $_SESSION[self::USER_SESSION_KEY] ) ) {
			return $_SESSION[self::USER_SESSION_KEY];

		} else {
			return null;
		}
	}


	/**
	 * Get the current user's Id.
	 * @return int|bool Numeric user id or false if not available
	 */
	public function getUserId() {
		$user = $this->getUserData();
		return $user ? $user->getId() : false;
	}


	/**
	 * Store the user's information.
	 * @param UserData $user User information
	 */
	public function setUser( UserData $user ) {
		$_SESSION[self::USER_SESSION_KEY] = $user;
	}


	/**
	 * Is the user authenticated?
	 * @return bool True if authenticated, false otherwise
	 */
	public function isAuthenticated() {
		return $this->getUserData() !== null;
	}


	/**
	 * Is the user anonymous?
	 * @return bool True if the user is not authenticated, false otherwise
	 */
	public function isAnonymous() {
		return $this->getUserData() === null;
	}


	/**
	 * Attempt to authenticate a user.
	 * @param string $uname Username
	 * @param string $password Password
	 * @return bool True if authentication is successful, false otherwise
	 */
	public function authenticate( $uname, $password ) {
		$user = $this->manager->getUserData( $uname );
		$check = Password::comparePasswordToHash( $password, $user->getPassword() );
		if ( $check && !$user->isBlocked() ) {
			$this->login( $user );
			return true;

		} else {
			return false;
		}
	}


	/**
	 * Add authentication.
	 *
	 * @param UserData $user
	 */
	public function login( UserData $user ) {
		// clear session
		foreach ( $_SESSION as $key => $value ) {
			unset( $_SESSION[$key] );
		}

		// generate new session id
		session_regenerate_id( true );

		// store user info in session
		$this->setUser( $user );
	}


	/**
	 * Remove authentication.
	 */
	public function logout() {
		// clear session
		foreach ( $_SESSION as $key => $value ) {
			unset( $_SESSION[$key] );
		}

		// delete the session cookie on the client
		if ( ini_get( 'session.use_cookies' ) ) {
			$params = session_get_cookie_params();
			setcookie( session_name(), '', time() - 42000,
				$params['path'], $params['domain'],
				$params['secure'], $params['httponly']
			);
		}

		// destroy local session storage
		session_destroy();
		// generate new session id
		session_regenerate_id( true );
	}

}
