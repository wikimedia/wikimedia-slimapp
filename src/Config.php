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
 * Configuration registry.
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Config {

	/**
	 * Get a boolean value
	 * @param string $name Setting name
	 * @param bool $default Default value if none found
	 * @return bool Value
	 */
	public static function getBool( $name, $default = false ) {
		$var = getenv( $name );
		$val = filter_var( $var, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE );
		return ( $val === null ) ? $default : $val;
	}


	/**
	 * Get a string value
	 * @param string $name Setting name
	 * @param string $default Default value if none found
	 * @return string Value
	 */
	public static function getStr( $name, $default = '' ) {
		$var = getenv( $name );
		if ( $var !== false ) {
			$var = filter_var( $var,
				\FILTER_SANITIZE_STRING,
				\FILTER_FLAG_STRIP_LOW | \FILTER_FLAG_STRIP_HIGH
			);
		}
		return ( $var === false ) ? $default : $var;
	}


	/**
	 * Get a date value
	 * @param string $name Setting name
	 * @return int|bool Unix timestamp or false if not found
	 */
	public static function getDate( $name ) {
		return strtotime( self::getStr( $name ) );
	}


	/**
	 * Load configuration data from file
	 *
	 * Reads ini file style configuration settings from the given file and
	 * loads the values into the application's environment. This is useful in
	 * deployments where the use of the container environment for configuration
	 * is discouraged.
	 *
	 * @param string $file Path to config file
	 */
	public static function load( $file ) {
		if ( !is_readable( $file ) ) {
			throw new \InvalidArgumentException( "File '{$file}' is not readable." );
		}

		$settings = parse_ini_file( $file );

		foreach ( $settings as $key => $value ) {
				// Store in super globals
				$_ENV[$key] = $value;
				$_SERVER[$key] = $value;

				// Also store in process env vars
				putenv( "{$key}={$value}" );
		} // end foreach settings
	}

}
