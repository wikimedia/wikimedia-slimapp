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
 * @coversDefaultClass \Wikimedia\Slimapp\Form
 * @uses \Wikimedia\Slimapp\Form
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class FormTest extends TestCase {

	public function testRequired() {
		$form = new Form();
		$form->requireString( 'foo' );

		$this->assertFalse( $form->validate(), 'Form should be invalid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertNull( $vals['foo'] );
		$this->assertContains( 'foo', $form->getErrors() );
	}

	public function testDefaultWhenEmpty() {
		$form = new Form();
		$form->expectString( 'foo', [ 'default' => 'bar' ] );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertNull( $vals['foo'] );
		$this->assertSame( 'bar', $form->get( 'foo' ) );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testNotInArray() {
		$form = new Form();
		$form->requireInArray( 'foo', [ 'bar' ] );

		$this->assertFalse( $form->validate(), 'Form should be invalid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertNull( $vals['foo'] );
		$this->assertContains( 'foo', $form->getErrors() );
	}

	public function testInArray() {
		$_POST['foo'] = 'bar';
		$form = new Form();
		$form->requireInArray( 'foo', [ 'bar' ] );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertEquals( 'bar', $vals['foo'] );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testNotInArrayNotRequired() {
		unset( $_POST['foo'] );
		$form = new Form();
		$form->expectInArray( 'foo', [ 'bar' ] );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertEmpty( $vals['foo'] );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testMultipleInputs() {
		$data = [
			'bool' => false,
			'true' => true,
			'email' => 'user!user2+route@example.wiki',
			'float' => 1.23,
			'int' => 123,
			'ipv4' => '127.0.0.1',
			'ipv6' => '::1',
			'regex' => 'abc',
			'url' => 'proto://host.tld/path',
			'str' => 'one two three',
		];
		$form = new Form();
		$form->requireBool( 'bool' );
		$form->requireTrue( 'true' );
		$form->requireEmail( 'email' );
		$form->requireFloat( 'float' );
		$form->requireInt( 'int' );
		$form->requireIp( 'ipv4' );
		$form->requireIp( 'ipv6' );
		$form->requireRegex( 'regex', '/^ABC$/i' );
		$form->requireUrl( 'url' );
		$form->requireString( 'str' );

		$this->assertTrue( $form->validate( $data ), 'Form should be valid' );
		$this->assertSame( $data, $form->getValues() );
	}

	public function testArrayValues() {
		$data = [
			'bool' => [ true, false ],
			'float' => [ 1.23, 4.56 ],
			'int' => [ 123, 456 ],
			'str' => [ 'one', 'two', 'three' ],
		];
		$form = new Form();
		$form->requireBoolArray( 'bool' );
		$form->requireFloatArray( 'float' );
		$form->requireIntArray( 'int' );
		$form->requireStringArray( 'str' );

		$this->assertTrue( $form->validate( $data ), 'Form should be valid' );
		$this->assertSame( $data, $form->getValues() );
	}

	public function testArrayValueErrors() {
		$data = [
			'int' => [ 123, 456, 'xyzzy', '678' ],
		];
		$form = new Form();
		$form->requireIntArray( 'int' );
		$this->assertFalse( $form->validate( $data ), 'Form should not be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'int', $vals );
		$this->assertSame( [ 123, 456, 3 => 678 ], $vals['int'] );
		$this->assertContains( 'int[2]', $form->getErrors() );
	}

	/**
	 * @dataProvider provideExpectDateTime
	 * @param string $input
	 * @param string $format
	 * @param bool $valid
	 */
	public function testExpectDateTime( $input, $format, $valid ) {
		$_POST['date'] = $input;
		$form = new Form();
		$form->requireDateTime( 'date', $format );
		if ( $valid ) {
			$this->assertTrue( $form->validate(), 'Form should be valid' );
			$vals = $form->getValues();
			$this->assertArrayHasKey( 'date', $vals );
			$this->assertInstanceOf( 'DateTime', $vals['date'] );
			$this->assertEquals( $input, $vals['date']->format( $format ) );
			$this->assertNotContains( 'date', $form->getErrors() );
		} else {
			$this->assertFalse( $form->validate(), 'Form should be invalid' );
			$vals = $form->getValues();
			$this->assertArrayHasKey( 'date', $vals );
			$this->assertNull( $vals['date'] );
			$this->assertContains( 'date', $form->getErrors() );
		}
	}

	public function provideExpectDateTime() {
		return [
			[ '2014-12-08', 'Y-m-d', true ],
			[ '2014-12-08 23:02', 'Y-m-d H:i', true ],
			[ '11:37', 'H:i', true ],
			[ '2014-13-1', 'Y-m-d', false ],
			[ '2014-2-29', 'Y-m-d', false ],
			[ '2014-12-08 23:02', 'Y-m-d h:i', false ],
			[ '27:37', 'H:i', false ],
		];
	}

	public function testEncodeBasic() {
		$input = [
			'foo' => 1,
			'bar' => 'this=that',
			'baz' => 'tom & jerry',
		];
		$output = Form::urlEncode( $input );
		$this->assertEquals( 'foo=1&bar=this%3Dthat&baz=tom+%26+jerry', $output );
	}

	public function testEncodeArray() {
		$input = [
			'foo' => [ 'a', 'b', 'c' ],
			'bar[]' => [ 1, 2, 3 ],
		];
		$output = Form::urlEncode( $input );
		$this->assertEquals(
			'foo=a&foo=b&foo=c&bar%5B%5D=1&bar%5B%5D=2&bar%5B%5D=3', $output );
	}

	public function testQsMerge() {
		$_GET['foo'] = 1;
		$_GET['bar'] = 'this=that';
		$_GET['baz'] = 'tom & jerry';

		$output = Form::qsMerge();
		$this->assertEquals( 'foo=1&bar=this%3Dthat&baz=tom+%26+jerry', $output );

		$output = Form::qsMerge( [ 'foo' => 2, 'xyzzy' => 'grue' ] );
		$this->assertEquals( 'foo=2&bar=this%3Dthat&baz=tom+%26+jerry&xyzzy=grue', $output );
	}

	public function testQsRemove() {
		$_GET['foo'] = 1;
		$_GET['bar'] = 'this=that';
		$_GET['baz'] = 'tom & jerry';

		$output = Form::qsRemove();
		$this->assertEquals( 'foo=1&bar=this%3Dthat&baz=tom+%26+jerry', $output );

		$output = Form::qsRemove( [ 'bar' ] );
		$this->assertEquals( 'foo=1&baz=tom+%26+jerry', $output );
	}
}
