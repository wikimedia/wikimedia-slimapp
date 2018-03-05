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
 * @coversDefaultClass \Wikimedia\Slimapp\Auth\Password
 * @uses \Wikimedia\Slimapp\Auth\Password
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class PasswordTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @covers ::encodePassword
	 * @covers ::blowfishSalt
	 */
	public function testUniqueEncoding() {
		$enc = Password::encodePassword( 'password' );
		$enc2 = Password::encodePassword( 'password' );
		$this->assertNotEquals( $enc, $enc2 );
	}

	/**
	 * @covers ::comparePasswordToHash
	 * @covers ::hashEquals
	 */
	public function testComparePasswordToHash() {
		$enc = Password::encodePassword( 'password' );
		$this->assertTrue( Password::comparePasswordToHash( 'password', $enc ) );
		$this->assertFalse( Password::comparePasswordToHash( 'Password', $enc ) );
	}

	/**
	 * @covers ::randomPassword
	 */
	public function testRandomPassword() {
		// I've always wondered how to write a phpunit test to decide if random is
		// random. For now I'll settle for testing to see if I get the expected
		// number of characters.
		$p = Password::randomPassword( 16 );
		$this->assertEquals( 16, strlen( $p ) );
	}

	/**
	 * @covers ::hashEquals
	 */
	public function testHashEquals() {
		// Do not count warnings from a native hash_equals() implementation
		// as errors.
		\PHPUnit_Framework_Error_Warning::$enabled = false;

		$this->assertFalse( Password::hashEquals( false, '' ) );
		$this->assertFalse( Password::hashEquals( '', false ) );
		$this->assertFalse( Password::hashEquals( 'a', '' ) );
		$this->assertFalse( Password::hashEquals( 'a', 'b' ) );
		$this->assertTrue( Password::hashEquals( 'a', 'a' ) );
	}
}
