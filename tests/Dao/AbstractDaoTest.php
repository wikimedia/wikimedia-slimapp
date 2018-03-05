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

namespace Wikimedia\Slimapp\Dao;

/**
 * @coversDefaultClass \Wikimedia\Slimapp\Dao\AbstractDao
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class AbstractDaoTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider provideBuildWhere
	 */
	public function testBuildWhere( array $where, $conjunction, $expect ) {
		$fixture = $this->getMockBuilder( 'Wikimedia\Slimapp\Dao\AbstractDao' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$clazz = new \ReflectionClass( 'Wikimedia\Slimapp\Dao\AbstractDao' );
		$buildWhere = $clazz->getMethod( 'buildWhere' );
		$buildWhere->setAccessible( true );

		$this->assertSame(
			$expect,
			$buildWhere->invokeArgs( $fixture, [ $where, $conjunction ] )
		);
	}

	public function provideBuildWhere() {
		return [
			'empty' => [ [], 'AND', '' ],
			'1 arg' => [ [ 'foo=bar' ], 'AND', 'WHERE (foo=bar) ' ],
			'2 args' => [
				[ 'foo=bar', 'baz=quuxx' ],
				'OR',
				'WHERE (foo=bar) OR (baz=quuxx) '
			],
		];
	}

	/**
	 * @dataProvider provideBuildHaving
	 */
	public function testBuildHaving( array $having, $conjunction, $expect ) {
		$fixture = $this->getMockBuilder( 'Wikimedia\Slimapp\Dao\AbstractDao' )
			->disableOriginalConstructor()
			->getMockForAbstractClass();

		$clazz = new \ReflectionClass( 'Wikimedia\Slimapp\Dao\AbstractDao' );
		$buildHaving = $clazz->getMethod( 'buildHaving' );
		$buildHaving->setAccessible( true );

		$this->assertSame(
			$expect,
			$buildHaving->invokeArgs( $fixture, [ $having, $conjunction ] )
		);
	}

	public function provideBuildHaving() {
		return [
			'empty' => [ [], 'AND', '' ],
			'1 arg' => [ [ 'foo=bar' ], 'AND', 'HAVING (foo=bar) ' ],
			'2 args' => [
				[ 'foo=bar', 'baz=quuxx' ],
				'OR',
				'HAVING (foo=bar) OR (baz=quuxx) '
			],
		];
	}
}
