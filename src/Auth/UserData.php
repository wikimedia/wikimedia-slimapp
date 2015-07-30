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

use \PDOException;

/**
 * Basic user information.
 *
 * Implementations must be serializable.
 *
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
interface UserData {

	/**
	 * Get user's unique numeric id.
	 * @return int
	 */
	public function getId();

	/**
	 * Get user's password.
	 * @return string
	 */
	public function getPassword();

	/**
	 * Is this user blocked from logging into the application?
	 * @return bool True if user should not be allowed to log in to the
	 *   application, false otherwise
	 */
	public function isBlocked();
}
