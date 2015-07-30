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
 * @coversDefaultClass \Wikimedia\Slimapp\Form
 * @uses \Wikimedia\Slimapp\Form
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class FormTest extends \PHPUnit_Framework_TestCase {

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
		$form->expectString( 'foo', array( 'default' => 'bar' ) );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertNull( $vals['foo'] );
		$this->assertSame( 'bar', $form->get( 'foo' ) );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testNotInArray() {
		$form = new Form();
		$form->requireInArray( 'foo', array( 'bar' ) );

		$this->assertFalse( $form->validate(), 'Form should be invalid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertNull( $vals['foo'] );
		$this->assertContains( 'foo', $form->getErrors() );
	}

	public function testInArray() {
		$_POST['foo'] = 'bar';
		$form = new Form();
		$form->requireInArray( 'foo', array( 'bar' ) );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertEquals( 'bar', $vals['foo'] );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testNotInArrayNotRequired() {
		unset( $_POST['foo'] );
		$form = new Form();
		$form->expectInArray( 'foo', array( 'bar' ) );

		$this->assertTrue( $form->validate(), 'Form should be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'foo', $vals );
		$this->assertEquals( '', $vals['foo'] );
		$this->assertNotContains( 'foo', $form->getErrors() );
	}

	public function testMultipleInputs() {
		$data = array(
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
		);
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
		$data = array(
			'bool' => array( true, false ),
			'float' => array( 1.23, 4.56 ),
			'int' => array( 123, 456 ),
			'str' => array( 'one', 'two', 'three' ),
		);
		$form = new Form();
		$form->requireBoolArray( 'bool' );
		$form->requireFloatArray( 'float' );
		$form->requireIntArray( 'int' );
		$form->requireStringArray( 'str' );

		$this->assertTrue( $form->validate( $data ), 'Form should be valid' );
		$this->assertSame( $data, $form->getValues() );
	}

	public function testArrayValueErrors() {
		$data = array(
			'int' => array( 123, 456, 'xyzzy', '678' ),
		);
		$form = new Form();
		$form->requireIntArray( 'int' );
		$this->assertFalse( $form->validate( $data ), 'Form should not be valid' );
		$vals = $form->getValues();
		$this->assertArrayHasKey( 'int', $vals );
		$this->assertSame( array( 123, 456, 3 => 678 ), $vals['int'] );
		$this->assertContains( 'int[2]', $form->getErrors() );
	}

	public function testEncodeBasic() {
		$input = array(
			'foo' => 1,
			'bar' => 'this=that',
			'baz' => 'tom & jerry',
		);
		$output = Form::urlEncode( $input );
		$this->assertEquals( 'foo=1&bar=this%3Dthat&baz=tom+%26+jerry', $output );
	}

	public function testEncodeArray() {
		$input = array(
			'foo' => array( 'a', 'b', 'c' ),
			'bar[]' => array( 1, 2, 3 ),
		);
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

		$output = Form::qsMerge( array( 'foo' => 2, 'xyzzy' => 'grue' ) );
		$this->assertEquals( 'foo=2&bar=this%3Dthat&baz=tom+%26+jerry&xyzzy=grue', $output );
	}

	public function testQsRemove() {
		$_GET['foo'] = 1;
		$_GET['bar'] = 'this=that';
		$_GET['baz'] = 'tom & jerry';

		$output = Form::qsRemove();
		$this->assertEquals( 'foo=1&bar=this%3Dthat&baz=tom+%26+jerry', $output );

		$output = Form::qsRemove( array( 'bar' ) );
		$this->assertEquals( 'foo=1&baz=tom+%26+jerry', $output );
	}
}
