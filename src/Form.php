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
 * Collect and validate user input.
 *
 * Wraps PHP's built-in filter_var_array function to collect GET or POST input
 * as a collection of sanitized data.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Form {

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * Input parameters to expect.
	 * @var array $params
	 */
	protected $params = [];

	/**
	 * Values received after filtering.
	 * @var array $values
	 */
	protected $values = [];

	/**
	 * Fields with errors.
	 * @var array $errors
	 */
	protected $errors = [];

	/**
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
	}

	/**
	 * Add an input expectation.
	 *
	 * Allowed options:
	 * - default: Default value for missing input
	 * - flags: Flags to pass to filter_var_array
	 * - required: Is this input required?
	 * - validate: Callable to perform additional validation
	 * - callback: Callable for \FILTER_CALLBACK validation
	 *
	 * @param string $name Parameter to expect
	 * @param int $filter Validation filter(s) to apply
	 * @param array $options Validation options
	 * @return Form Self, for message chaining
	 */
	public function expect( $name, $filter, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		$flags = null;
		$required = false;
		$validate = null;

		if ( isset( $options['flags'] ) ) {
			$flags = $options['flags'];
			unset( $options['flags'] );
		}

		if ( isset( $options['required'] ) ) {
			$required = $options['required'];
			unset( $options['required'] );
		}

		if ( isset( $options['validate'] ) ) {
			$validate = $options['validate'];
			unset( $options['validate'] );
		}

		if ( $filter === \FILTER_CALLBACK ) {
			if ( !isset( $options['callback'] ) ||
				!is_callable( $options['callback'] )
			) {
				throw new \InvalidArgumentException(
					'FILTER_CALLBACK requires a valid callback.'
				);
			}
			$options = $options['callback'];
		}

		$this->params[$name] = [
			'filter'   => $filter,
			'flags'    => $flags,
			'options'  => $options,
			'required' => $required,
			'validate' => $validate,
		];

		return $this;
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectBool( $name, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		if ( !isset( $options['default'] ) ) {
			$options['default'] = false;
		}
		return $this->expect( $name, \FILTER_VALIDATE_BOOLEAN, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireBool( $name, $options = null ) {
		return $this->expectBool( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectBoolArray( $name, $options = null ) {
		return $this->expectBool( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireBoolArray( $name, $options = null ) {
		return $this->requireBool( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectTrue( $name, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		$options['validate'] = function ( $v ) {
			return (bool)$v;
		};
		return $this->expectBool( $name, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireTrue( $name, $options = null ) {
		return $this->expectTrue( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectTrueArray( $name, $options = null ) {
		return $this->expectTrue( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireTrueArray( $name, $options = null ) {
		return $this->requireTrue( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectEmail( $name, $options = null ) {
		return $this->expect( $name, \FILTER_VALIDATE_EMAIL, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireEmail( $name, $options = null ) {
		return $this->expectEmail( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectEmailArray( $name, $options = null ) {
		return $this->expectEmail( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireEmailArray( $name, $options = null ) {
		return $this->requireEmail( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectFloat( $name, $options = null ) {
		return $this->expect( $name, \FILTER_VALIDATE_FLOAT, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireFloat( $name, $options = null ) {
		return $this->expectFloat( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectFloatArray( $name, $options = null ) {
		return $this->expectFloat( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireFloatArray( $name, $options = null ) {
		return $this->requireFloat( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectInt( $name, $options = null ) {
		return $this->expect( $name, \FILTER_VALIDATE_INT, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireInt( $name, $options = null ) {
		return $this->expectInt( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectIntArray( $name, $options = null ) {
		return $this->expectInt( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireIntArray( $name, $options = null ) {
		return $this->requireInt( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectIp( $name, $options = null ) {
		return $this->expect( $name, \FILTER_VALIDATE_IP, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireIp( $name, $options = null ) {
		return $this->expectIp( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectIpArray( $name, $options = null ) {
		return $this->expectIp( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireIpArray( $name, $options = null ) {
		return $this->requireIp( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param string $re Regular expression
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectRegex( $name, $re, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		$options['regexp'] = $re;
		return $this->expect( $name, \FILTER_VALIDATE_REGEXP, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param string $re Regular expression
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireRegex( $name, $re, $options = null ) {
		return $this->expectRegex( $name, $re, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param string $re Regular expression
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectRegexArray( $name, $re, $options = null ) {
		return $this->expectRegex( $name, $re, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param string $re Regular expression
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireRegexArray( $name, $re, $options = null ) {
		return $this->requireRegex( $name, $re, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectUrl( $name, $options = null ) {
		return $this->expect( $name, \FILTER_VALIDATE_URL, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireUrl( $name, $options = null ) {
		return $this->expectUrl( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectUrlArray( $name, $options = null ) {
		return $this->expectUrl( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireUrlArray( $name, $options = null ) {
		return $this->requireUrl( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectString( $name, $options = null ) {
		return $this->expectRegex( $name, '/^.+$/s', $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireString( $name, $options = null ) {
		return $this->expectString( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectStringArray( $name, $options = null ) {
		return $this->expectString( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireStringArray( $name, $options = null ) {
		return $this->requireString( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectAnything( $name, $options = null ) {
		return $this->expect( $name, \FILTER_UNSAFE_RAW, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireAnything( $name, $options = null ) {
		return $this->expectAnything( $name, self::required( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectAnythingArray( $name, $options = null ) {
		return $this->expectAnything( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireAnythingArray( $name, $options = null ) {
		return $this->requireAnything( $name, self::wantArray( $options ) );
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $valids Valid values
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectInArray( $name, $valids, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		$required = isset( $options['required'] ) ? $options['required'] : false;
		$options['validate'] = function ( $val ) use ( $valids, $required ) {
			return ( !$required && empty( $val ) ) || in_array( $val, $valids );
		};
		return $this->expectAnything( $name, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $valids Valid values
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireInArray( $name, $valids, $options = null ) {
		return $this->expectInArray( $name, $valids,
			self::required( $options )
		);
	}

	/**
	 * @param string $name Parameter to expect
	 * @param array $valids Valid values
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function expectInArrayArray( $name, $valids, $options = null ) {
		return $this->expectInArray(
			$name, $values, self::wantArray( $options )
		);
	}

	/**
	 * @param string $name Parameter to require
	 * @param array $valids Valid values
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireInArrayArray( $name, $valids, $options = null ) {
		return $this->requireInArray(
			$name, $values, self::wantArray( $options )
		);
	}

	/**
	 * Add an input expectation for a DateTime object.
	 *
	 * @param string $name Parameter to expect
	 * @param string $format Expected date/time format
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 * @see DateTime::createFromFormat
	 */
	public function expectDateTime( $name, $format, $options = null ) {
		$options = ( is_array( $options ) ) ? $options : [];
		$options['callback'] = function ( $value ) use ( $format ) {
			try {
				$date = \DateTime::createFromFormat( $format, $value );
				$formatErrors = \DateTime::getLastErrors();
				if ( $formatErrors['error_count'] == 0 &&
					$formatErrors['warning_count'] == 0
				) {
					return $date;
				}
			} catch ( \Exception $ignored ) {
				// no-op
			}
			return false;
		};
		return $this->expect( $name, \FILTER_CALLBACK, $options );
	}

	/**
	 * @param string $name Parameter to require
	 * @param string $format Expected date/time format
	 * @param array $options Additional options
	 * @return Form Self, for message chaining
	 */
	public function requireDateTime( $name, $format, $options = null ) {
		return $this->expectDateTime( $name, $format,
			self::required( $options )
		);
	}

	/**
	 * Validate the provided input data using this form's expectations.
	 *
	 * @param array $vars Input to validate (default $_POST)
	 * @return bool True if input is valid, false otherwise
	 */
	public function validate( $vars = null ) {
		$vars = $vars ?: $_POST;
		$this->values = [];
		$this->errors = [];
		$arrayInvalids = [];

		$cleaned = filter_var_array( $vars, $this->params );

		foreach ( $this->params as $name => $opt ) {
			$clean = isset( $vars[$name] ) ? $cleaned[$name] : null;

			if ( $clean === false &&
				$opt['filter'] !== \FILTER_VALIDATE_BOOLEAN
			) {
				$this->values[$name] = null;

			} elseif ( is_array( $clean ) &&
				( $opt['flags'] & \FILTER_REQUIRE_ARRAY ) &&
				$opt['filter'] !== \FILTER_VALIDATE_BOOLEAN
			) {
				// Strip invalid value markers from input array
				$this->values[$name] = [];
				foreach ( $clean as $key => $value ) {
					if ( $opt['filter'] !== \FILTER_VALIDATE_BOOLEAN &&
						$value !== false
					) {
						$this->values[$name][$key] = $value;

					} elseif ( $opt['filter'] === \FILTER_VALIDATE_BOOLEAN &&
						$value !== null
					) {
						$this->values[$name][$key] = $value;

					} else {
						// Keep track of invalid keys in case input was
						// required
						if ( !isset( $arrayInvalids[$name] ) ) {
							$arrayInvalids[$name] = [];
						}
						$arrayInvalids[$name][] = "{$name}[{$key}]";
					}
				}

			} else {
				$this->values[$name] = $clean;
			}

			if ( $opt['required'] && $this->values[$name] === null ) {
				$this->errors[] = $name;

			} elseif ( $opt['required'] && isset( $arrayInvalids[$name] ) ) {
				$this->errors = array_merge(
					$this->errors, $arrayInvalids[$name]
				);

			} elseif ( is_callable( $opt['validate'] ) &&
				call_user_func( $opt['validate'], $this->values[$name] ) === false
			) {
				$this->errors[] = $name;
				$this->values[$name] = null;
			}
		}

		$this->customValidationHook();

		return count( $this->errors ) === 0;
	}

	/**
	 * Stub method that can be extended by subclasses to add additional
	 * validation logic.
	 */
	protected function customValidationHook() {
	}

	/**
	 * @param string $name Parameter name
	 * @return mixed Parameter value
	 */
	public function get( $name ) {
		if ( isset( $this->values[$name] ) ) {
			return $this->values[$name];

		} elseif ( isset( $this->params[$name]['options']['default'] ) ) {
			return $this->params[$name]['options']['default'];

		} else {
			return null;
		}
	}

	/**
	 * @return array Form values
	 */
	public function getValues() {
		return $this->values;
	}

	/**
	 * @return array Form errors
	 */
	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @return bool True if form has errors, false otherwise.
	 */
	public function hasErrors() {
		return count( $this->errors ) !== 0;
	}

	/**
	 * Make a URL-encoded string from a key=>value array
	 * @param array $parms Parameter array
	 * @return string URL-encoded message body
	 */
	public static function urlEncode( $parms ) {
		$payload = [];

		foreach ( $parms as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $item ) {
					$payload[] = urlencode( $key ) . '=' . urlencode( $item );
				}
			} else {
				$payload[] = urlencode( $key ) . '=' . urlencode( $value );
			}
		}

		return implode( '&', $payload );
	}

	/**
	 * Merge parameters into current query string.
	 * @param array $params Parameter array
	 * @return string URL-encoded message body
	 */
	public static function qsMerge( $params = [] ) {
		return self::urlEncode( array_merge( $_GET, $params ) );
	}

	/**
	 * Remove parameters from current query string.
	 * @param array $params Parameters to remove
	 * @return string URL-encoded message body
	 */
	public static function qsRemove( $params = [] ) {
		return self::urlEncode( array_diff_key( $_GET, array_flip( $params ) ) );
	}

	/**
	 * Ensure that the given options collection contains a 'required' key.
	 *
	 * @param array $options
	 * @return array
	 */
	protected static function required( $options ) {
		return array_merge( [ 'required' => true ], (array)$options );
	}

	/**
	 * Ensure that the given options collection contains a 'flags' key that
	 * requires the input to be an array.
	 *
	 * @param array $options
	 * @return array
	 */
	protected static function wantArray( $options ) {
		$options = array_merge( [ 'flags' => 0 ], (array)$options );
		$options['flags'] = $options['flags'] | \FILTER_REQUIRE_ARRAY;
		return $options;
	}
}
