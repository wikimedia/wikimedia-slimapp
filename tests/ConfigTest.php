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

use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Wikimedia\Slimapp\Config
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class ConfigTest extends TestCase {

	public function testLoad() {
		Config::load( __DIR__ . '/fixtures/ConfigTest.env' );

		$this->assertEnv( 'foo', 'FOO' );
		$this->assertEnv( 'with internal spaces', 'SPACED' );
		$this->assertEnv( '', 'EMPTY' );

		$this->assertEnv( 'foo', 'QFOO' );
		$this->assertEnv( 'with internal spaces', 'QSPACED' );
		$this->assertEnv( '', 'QEMPTY' );
		$this->assertEnv( '"hello world"', 'QESC' );

		$this->assertEnv( "this isn't simple = true;", 'COMPLEX' );
	}

	/**
	 * Assert that an expected value is present in getenv(), $_ENV and $_SERVER.
	 * @param string $expect Expected value
	 * @param string $var Variable to assert on
	 */
	protected function assertEnv( $expect, $var ) {
		$this->assertEquals( $expect, getenv( $var ) );
		$this->assertEquals( $expect, $_ENV[$var] );
		$this->assertEquals( $expect, $_SERVER[$var] );
	}

	public function testGetStrDefault() {
		$name = 'CONFIG_TEST_VALUE_NOT_SET';
		putenv( $name );
		$this->assertFalse( getenv( $name ) );

		$default = __METHOD__;
		$this->assertSame( $default, Config::getStr( $name, $default ) );
	}

	public function testGetDateUnset() {
		$name = 'CONFIG_TEST_VALUE_NOT_SET';
		putenv( $name );
		$this->assertFalse( getenv( $name ) );

		$this->assertFalse( Config::getDate( $name ) );
	}

}
