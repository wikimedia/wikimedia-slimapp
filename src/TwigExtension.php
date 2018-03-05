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
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class TwigExtension extends \Twig_Extension {

	/**
	 * @var ParsoidClient $parsoid
	 */
	protected $parsoid;

	/**
	 * @param ParsoidClient $parsoid ParsoidClient
	 */
	public function __construct( ParsoidClient $parsoid ) {
		$this->parsoid = $parsoid;
	}

	/**
	 * @return string Extension name
	 */
	public function getName() {
		return 'wikimedia-slimapp';
	}

	/**
	 * @return array Extension functions
	 */
	public function getFunctions() {
		return [
			new \Twig_SimpleFunction( 'qsMerge', [ $this, 'qsMerge' ] ),
		];
	}

	/**
	 * @return array Extension filters
	 */
	public function getFilters() {
		return [
			new \Twig_SimpleFilter(
				'wikitext', [ $this, 'wikitextFilterCallback' ],
				[ 'is_safe' => [ 'html' ] ]
			),
		];
	}

	/**
	 * @param array $parms Parameter array
	 * @return string URL-encoded message body
	 */
	public function qsMerge( $parms ) {
		return Form::qsMerge( $parms );
	}

	/**
	 * @param string $text Wikitext
	 * @return string Parsed wikitext
	 */
	public function wikitextFilterCallback( $text ) {
		return $this->parsoid->parse( $text );
	}
}
