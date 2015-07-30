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

use \PDO;
use \PDOException;
use Psr\Log\LoggerInterface;

/**
 * Base class for data access objects.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
abstract class AbstractDao {

	/**
	 * @var PDO $db
	 */
	protected $dbh;

	/**
	 * @var LoggerInterface $logger
	 */
	protected $logger;


	/**
	 * @param string $dsn PDO data source name
	 * @param string $user Database user
	 * @param string $pass Database password
	 * @param LoggerInterface $logger Log channel
	 */
	public function __construct( $dsn, $user, $pass, $logger = null ) {
		$this->logger = $logger ?: new \Psr\Log\NullLogger();

		$this->dbh = new PDO( $dsn, $user, $pass,
			array(
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			)
		);
	}


	/**
	 * Bind values to a prepared statement.
	 *
	 * If an associative array of values is provided, the data type to use when
	 * binding will be inferred by looking for a "<type>_" prefix at the
	 * beginning of the array key. This can come in very handy if you are using
	 * parameters in places like LIMIT clauses where binding as a string (the
	 * default type for PDO binds) will cause a syntax error.
	 *
	 * @param \PDOStatement $stmt Previously prepared statement
	 * @param array $values Values to bind
	 */
	protected function bind( $stmt, $values ) {
		$values = $values ?: array();

		if ( (bool)count( array_filter( array_keys( $values ), 'is_string' ) ) ) {
			// associative array provided
			foreach ( $values as $key => $value ) {
				// infer bind type from key prefix
				list( $prefix, $ignored ) = explode( '_', "{$key}_", 2 );

				$type = \PDO::PARAM_STR;
				switch ( $prefix ) {
					case 'int':
						$type = \PDO::PARAM_INT;
						break;
					case 'bool':
						$type = \PDO::PARAM_BOOL;
						break;
					case 'null':
						$type = \PDO::PARAM_NULL;
						break;
					default:
						$type = \PDO::PARAM_STR;
				}

				$stmt->bindValue( $key, $value, $type );
			}

		} else {
			// vector provided
			$idx = 1;
			foreach ( $values as $value ) {
				$stmt->bindValue( $idx, $value );
				$idx++;
			}
		}
	}


	/**
	 * Prepare and execute an SQL statement and return the first row of results.
	 *
	 * @param string $sql SQL
	 * @param array $params Prepared statement parameters
	 * @return array First response row
	 */
	protected function fetch( $sql, $params = null ) {
		$stmt = $this->dbh->prepare( $sql );
		$this->bind( $stmt, $params );
		$stmt->execute();
		return $stmt->fetch();
	}


	/**
	 * Prepare and execute an SQL statement and return all results.
	 *
	 * @param string $sql SQL
	 * @param array $params Prepared statement parameters
	 * @return array Result rows
	 */
	protected function fetchAll( $sql, $params = null ) {
		$this->logger->debug( $sql, $params ?: array() );
		$stmt = $this->dbh->prepare( $sql );
		$this->bind( $stmt, $params );
		$stmt->execute();
		return $stmt->fetchAll();
	}


	/**
	 * Prepare and execute an SQL statement and return all results plus the
	 * number of rows found on the server side.
	 *
	 * The SQL is expected to contain the "SQL_CALC_FOUND_ROWS" option in the
	 * select statement. If it does not, the number of found rows returned is
	 * dependent on MySQL's interpretation of the query.
	 *
	 * @param string $sql SQL
	 * @param array $params Prepared statement parameters
	 * @return object StdClass with rows and found memebers
	 */
	protected function fetchAllWithFound( $sql, $params = null ) {
		$ret = new \StdClass;
		$ret->rows = $this->fetchAll( $sql, $params );

		$ret->found = $this->fetch( 'SELECT FOUND_ROWS() AS found' );
		$ret->found = $ret->found['found'];

		return $ret;
	}


	/**
	 * Prepare and execute an SQL statement in a transaction.
	 *
	 * @param string $sql SQL
	 * @param array $params Prepared statement parameters
	 * @return bool False if an exception was generated, true otherwise
	 */
	protected function update( $sql, $params = null ) {
		$stmt = $this->dbh->prepare( $sql );
		try {
			$this->dbh->begintransaction();
			$stmt->execute( $params );
			$this->dbh->commit();
			return true;

		} catch ( PDOException $e ) {
			$this->dbh->rollback();
			$this->logger->error( 'Update failed.', array(
				'method' => __METHOD__,
				'exception' => $e,
				'sql' => $sql,
				'params' => $params,
			) );
			return false;
		}
	}


	/**
	 * Prepare and execute an SQL statement in a transaction.
	 *
	 * @param string $sql SQL
	 * @param array $params Prepared statement parameters
	 * @return int|bool Last insert id or false if an exception was generated
	 */
	protected function insert( $sql, $params = null ) {
		$stmt = $this->dbh->prepare( $sql );
		try {
			$this->dbh->beginTransaction();
			$stmt->execute( $params );
			$rowid = $this->dbh->lastInsertId();
			$this->dbh->commit();
			return $rowid;

		} catch ( PDOException $e ) {
			$this->dbh->rollback();
			$this->logger->error( 'Insert failed.', array(
				'method' => __METHOD__,
				'exception' => $e,
				'sql' => $sql,
				'params' => $params,
			) );
			return false;
		}
	}


	/**
	 * Construct a where clause.
	 * @param array $where List of conditions
	 * @param string $conjunction Joining operation ('and' or 'or')
	 * @return string Where clause or empty string
	 */
	protected static function buildWhere( array $where, $conjunction = 'AND' ) {
		if ( $where ) {
			return 'WHERE (' . implode( ") {$conjunction} (", $where ) . ') ';
		}
		return '';
	}


	/**
	 * Create a string by joining all arguments with spaces.
	 *
	 * If one or more of the arguments are arrays each element of the array will
	 * be included independently.
	 *
	 * @return string New string
	 */
	protected static function concat( /*varags*/ ) {
		$args = array();
		foreach ( func_get_args() as $arg ) {
			if ( is_array( $arg ) ) {
				$args = array_merge( $args, $arg );
			} else {
				$args[] = $arg;
			}
		}

		return implode( ' ', $args );
	}

	/**
	 * Create a list of bind parameters from a list of strings.
	 *
	 * @param array $list List of strings to convert to bind parameters
	 * @return array List of bind parameters (eg ':field1)
	 */
	protected static function makeBindParams( array $list ) {
		return array_map( function ( $elm ) {
			return ":{$elm}";
		}, $list );
	}
}
