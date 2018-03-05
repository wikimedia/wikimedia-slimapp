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

use \PHPMailer;
use \phpmailerException;
use Psr\Log\LoggerInterface;

/**
 * Wrapper around PHPMailer
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
class Mailer {

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;

	/**
	 * @var array $settings
	 */
	protected $settings = [
		'AllowEmpty' => false,
		'CharSet' => 'utf-8',
		'ContentType' => 'text/plain',
		'From' => 'grants@wikimedia.org',
		'FromName' => 'Wikimedia Grants',
		'Mailer' => 'smtp',
		'WordWrap' => 72,
		'XMailer' => 'Wikimedia Grants review system',
	];

	/**
	 * @param array $settings Configuration settings for PHPMailer
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $settings = [], $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();
		$settings = is_array( $settings ) ? $settings : [];
		$this->settings = array_merge( $this->settings, $settings );
	}

	/**
	 * @param string $to Recipent(s)
	 * @param string $subject Subject
	 * @param string $message Message
	 * @param array $settings Additional settings
	 * @return bool Send status
	 */
	public function mail( $to, $subject, $message, $settings = [] ) {
		try {
			$mailer = $this->createMailer( $settings );
			$mailer->addAddress( $to );
			$mailer->Subject = $subject;
			$mailer->Body = $message;
			return $mailer->send();

		} catch ( phpmailerException $e ) {
			$this->logger->error( 'Failed to send message: {message}', [
				'method' => __METHOD__,
				'exception' => $e,
				'message' => $e->getMessage(),
			] );
		}
	}

	/**
	 * Create and configure a PHPMailer instance.
	 *
	 * @param array $settings Configuration settings
	 * @return PHPMailer New mailer configured with default, instance and local
	 * settings
	 */
	protected function createMailer( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : [];
		$mailer = new PHPMailer( true );
		foreach ( array_merge( $this->settings, $settings ) as $key => $value ) {
			$mailer->set( $key, $value );
		}
		return $mailer;
	}

}
