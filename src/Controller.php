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

use Wikimedia\Slimapp\Dao\AbstractDao;
use Wikimedia\SimpleI18n\I18nContext;

/**
 * Page controller.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Controller {

	/**
	 * @var \Slim\Slim $slim
	 */
	protected $slim;

	/**
	 * @var AbstractDao $dao
	 */
	protected $dao;

	/**
	 * @var Form $form
	 */
	protected $form;

	/**
	 * @var Mailer $mailer
	 */
	protected $mailer;

	/**
	 * @var I18nContext $i18nctx
	 */
	protected $i18nctx;

	/**
	 * @param \Slim\Slim $slim
	 */
	public function __construct( \Slim\Slim $slim = null ) {
		$this->slim = $slim ?: \Slim\Slim::getInstance();
		$this->form = new Form( $this->slim->log );
	}

	/**
	 * Set default DAO
	 * @param AbstractDao $dao
	 */
	public function setDao( AbstractDao $dao ) {
		$this->dao = $dao;
	}

	/**
	 * Set default form
	 * @param Form $form
	 */
	public function setForm( Form $form ) {
		$this->form = $form;
	}

	/**
	 * Set mailer
	 * @param Mailer $mailer
	 */
	public function setMailer( Mailer $mailer ) {
		$this->mailer = $mailer;
	}

	/**
	 * Set i18n context
	 * @param I18nContext $i18nctx
	 */
	public function setI18nContext( I18nContext $i18nctx ) {
		$this->i18nctx = $i18nctx;
	}

	/**
	 * Default request handler.
	 *
	 * Default implementation will pass()
	 */
	protected function handle() {
		$this->slim->pass();
	}

	/**
	 * Handle request by calling handleMethod on self.
	 *
	 * If no method matching the current request method is present then fall
	 * back to self::handle().
	 */
	public function __invoke() {
		$argv = func_get_args();
		$method = $this->slim->request->getMethod();
		$mname = 'handle' . ucfirst( strtolower( $method ) );
		if ( method_exists( $this, $mname ) ) {
			call_user_func_array( [ $this, $mname ], $argv );
		} else {
			call_user_func_array( [ $this, 'handle' ], $argv );
		}
	}

	/**
	 * Handle calls to undefined methods by proxying to the Slim member.
	 *
	 * @param string $name Method name
	 * @param array $args Call arguments
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		if ( method_exists( $this->slim, $name ) ) {
			return call_user_func_array( [ $this->slim, $name ], $args );
		}
		// emulate default PHP behavior
		trigger_error(
			'Call to undefined method ' . __CLASS__ . '::' . $name . '()',
			E_USER_ERROR
		);
	}

	/**
	 * Handle access to undefined member variables by proxying to the Slim
	 * member.
	 *
	 * @param string $name Memeber name
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->slim->{$name};
	}

	/**
	 * Get a flash message.
	 *
	 * @param string $key Message key
	 * @return mixed|null
	 */
	protected function flashGet( $key ) {
		if ( isset( $this->slim->environment['slim.flash'] ) ) {
			return $this->slim->environment['slim.flash'][$key];
		} else {
			return null;
		}
	}

	/**
	 * Get a message from the I18nContext.
	 *
	 * @param string $key Message name
	 * @param array $params Parameters to add to the message
	 * @return \Wikimedia\SimpleI18n\Message
	 */
	protected function msg( $key, $params = [] ) {
		return $this->i18nctx->message( $key, $params );
	}

	/**
	 * Compute pagination data.
	 *
	 * @param int $total Total records
	 * @param int $current Current page number (0-indexed)
	 * @param int $pageSize Number of items per page
	 * @param int $around Numer of pages to show on each side of current
	 * @return array Page count, first page index, last page index
	 */
	protected function pagination( $total, $current, $pageSize, $around = 4 ) {
		$pageCount = ceil( $total / $pageSize );
		$first = max( 0, $current - $around );
		$last = min( max( 0, $pageCount - 1 ), $current + 4 );
		return [ $pageCount, $first, $last ];
	}

}
