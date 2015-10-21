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
 * Password management utility.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Password {

	/**
	 * Blowfish hashing salt prefix for crypt.
	 * @var string BLOWFISH_PREFIX
	 */
	const BLOWFISH_PREFIX = '$2y$';


	/**
	 * Compare a plain text string to a stored password hash.
	 *
	 * @param string $plainText Password to check
	 * @param string $hash Stored hash to compare with
	 * @return bool True if plain text matches hash, false otherwise
	 */
	public static function comparePasswordToHash( $plainText, $hash ) {
		if ( self::isBlowfishHash( $hash ) ) {
			$check = crypt( $plainText, $hash );

		} else {
			// horrible unsalted md5 that legacy app used for passwords
			$check = md5( $plainText );
		}

		return self::hashEquals( $hash, $check );
	}


	/**
	 * Encode a password for database storage.
	 *
	 * Do not use the direct output of this function for comparison with stored
	 * values. Modern password hashes use unique salts per encoding and will not
	 * be directly comparable. Use the comparePasswordToHash() function for
	 * validation instead.
	 *
	 * @param string $plainText Password in plain text
	 * @return string Encoded password
	 */
	public static function encodePassword( $plainText ) {
		$salt = self::blowfishSalt();
		return crypt( $plainText, $salt );
	}


	/**
	 * Generate a blowfish salt specification.
	 *
	 * @param int $cost Cost factor
	 * @return string Blowfish salt
	 */
	public static function blowfishSalt( $cost = 8 ) {
		// encoding algorithm from http://www.openwall.com/phpass/
		$itoa = './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		if ( $cost < 4 || $cost > 31 ) {
			$cost = 8;
		}
		$random = self::getBytes( 16 );

		$output = self::BLOWFISH_PREFIX;
		$output .= chr( ord( '0' ) + $cost / 10 );
		$output .= chr( ord( '0' ) + $cost % 10 );
		$output .= '$';

		$i = 0;
		do {
			$c1 = ord( $random[$i++] );
			$output .= $itoa[$c1 >> 2];
			$c1 = ( $c1 & 0x03 ) << 4;
			if ( $i >= 16 ) {
				$output .= $itoa[$c1];
				break;
			}

			$c2 = ord( $random[$i++] );
			$c1 |= $c2 >> 4;
			$output .= $itoa[$c1];
			$c1 = ( $c2 & 0x0f ) << 2;

			$c2 = ord( $random[$i++] );
			$c1 |= $c2 >> 6;
			$output .= $itoa[$c1];
			$output .= $itoa[$c2 & 0x3f];
		} while ( 1 );

		return $output;
	}


	/**
	 * Get N high entropy random bytes.
	 *
	 * @param int $count Number of bytes to generate
	 * @param bool $allowWeak Allow weak entropy sources
	 * @return string String of random bytes
	 * @throws InvalidArgumentException if $allowWeak is false and no high
	 * entropy sources of random data can be found
	 */
	public static function getBytes( $count, $allowWeak = false ) {

		if ( function_exists( 'mcrypt_create_iv' ) ) {
			$bytes = mcrypt_create_iv( $count, MCRYPT_DEV_URANDOM );

			if ( strlen( $bytes ) === $count ) {
				return $bytes;
			}
		}

		if ( function_exists( 'openssl_random_pseudo_bytes' ) ) {
			$bytes = openssl_random_pseudo_bytes( $count, $strong );

			if ( $strong && strlen( $bytes ) === $count ) {
				return $bytes;
			}
		} // end if openssl_random_pseudo_bytes

		if ( is_readable( '/dev/urandom' ) ) {
			// @codingStandardsIgnoreStart : Silencing errors is discouraged
			$fh = @fopen( '/dev/urandom', 'rb' );
			// @codingStandardsIgnoreEnd
			if ( false !== $fh ) {
				$bytes = '';
				$have = 0;
				while ( $have < $count ) {
					$bytes .= fread( $fh, $count - $have );
					$have = strlen( $bytes );
				}
				fclose( $fh );

				if ( strlen( $bytes ) === $count ) {
					return $bytes;
				}
			}
		} // end if /dev/urandom

		if ( $allowWeak !== true ) {
			throw new InvalidArgumentException(
				'No high entropy source of random data found and ' .
				'weak sources disallowed in function call'
			);
		}

		// create a high entropy seed value
		$seed = microtime() . uniqid( '', true );
		if ( function_exists( 'getmypid' ) ) {
			$seed .= getmypid();
		}

		$bytes = '';
		for ( $i = 0; $i < $count; $i += 16 ) {
			$seed = md5( microtime() . $seed );
			$bytes .= pack( 'H*', md5( $seed ) );
		}

		return substr( $bytes, 0, $count );
	}


	/**
	 * Check a salt specification to see if it is a blowfish crypt value.
	 *
	 * @param string $hash Hash to check
	 * @return bool True if blowfish, false otherwise.
	 */
	public static function isBlowfishHash( $hash ) {
		$peek = strlen( self::BLOWFISH_PREFIX );
		return strlen( $hash ) == 60 &&
			substr( $hash, 0, $peek ) == self::BLOWFISH_PREFIX;
	}


	// @codingStandardsIgnoreStart : Line exceeds 100 characters
	const CHARSET_PRINTABLE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';
	// @codingStandardsIgnoreEnd
	const CHARSET_UPPER = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const CHARSET_LOWER = 'abcdefghijklmnopqrstuvwxyz';
	const CHARSET_DIGIT = '0123456789';
	const CHARSET_ALPHANUM = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
	const CHARSET_SYMBOL = '!"#$%&\'()*+,-./:;<=>?@[\]^_`{|}~';


	/**
	 * Generate a random password.
	 *
	 * Note: This is not the worlds greatest password generation algorithm. It
	 * uses a selection technique that has some bias based on modulo
	 * arithmetic. If you need a truely random password you'll need to look
	 * somewhere else. If you just need a temporary password to email to a user
	 * who will promptly log in and change their password to 'god', this should
	 * be good enough.
	 *
	 * @param int $len Length of password desired
	 * @param string $cs Symbol set to select password characters from
	 * @return string Password
	 */
	public static function randomPassword( $len, $cs = null ) {
		if ( $cs === null ) {
			$cs = self::CHARSET_PRINTABLE;
		}
		$csLen = strlen( $cs );

		$random = self::getBytes( $len, true );
		$password = '';

		foreach ( range( 0, $len - 1 ) as $i ) {
			$password .= $cs[ ord( $random[$i] ) % $csLen ];
		}

		return $password;
	}


	/**
	 * Check whether a user-provided string is equal to a fixed-length secret
	 * string without revealing bytes of the secret string through timing
	 * differences.
	 *
	 * Implementation for PHP deployments which do not natively have
	 * hash_equals taken from MediaWiki's hash_equals() polyfill function.
	 *
	 * @param string $known Fixed-length secret string to compare against
	 * @param string $input User-provided string
	 * @return bool True if the strings are the same, false otherwise
	 */
	public static function hashEquals( $known, $input ) {
		if ( function_exists( 'hash_equals' ) ) {
			return hash_equals( $known, $input );

		} else {
			// hash_equals() polyfill taken from MediaWiki
			if ( !is_string( $known ) ) {
				return false;
			}
			if ( !is_string( $input ) ) {
				return false;
			}

			$len = strlen( $known );
			if ( $len !== strlen( $input ) ) {
				return false;
			}

			$result = 0;
			for ( $i = 0; $i < $len; $i++ ) {
				$result |= ord( $known[$i] ) ^ ord( $input[$i] );
			}

			return $result === 0;
		}
	}


	/**
	 * Construction of utility class is not allowed.
	 */
	private function __construct() {
		// no-op
	}

}
