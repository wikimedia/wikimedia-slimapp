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

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;

/**
 * Base class for data access objects.
 *
 * @author Bryan Davis <bd808@wikimedia.org>
 * @copyright © 2015 Bryan Davis, Wikimedia Foundation and contributors.
 */
abstract class AbstractDao {

	/**
	 * @var PDO
	 */
	protected $dbh;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var int Used for keeping track of transaction status
	 */
	protected $transactionCounter = 0;

	/**
	 * @param string $dsn PDO data source name
	 * @param string $user Database user
	 * @param string $pass Database password
	 * @param LoggerInterface|null $logger Log channel
	 */
	public function __construct( $dsn, $user, $pass, $logger = null ) {
		$this->logger = $logger ?: new NullLogger();

		$this->dbh = new PDO( $dsn, $user, $pass,
			[
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			]
		);
	}

	/**
	 * Start a new transaction
	 *
	 * If already a transaction has been started, it will only increment the
	 * counter. This method is useful in nested transactions.
	 * @return bool True on success, false on failure.
	 */
	protected function transactionStart() {
		if ( $this->transactionCounter === 0 ) {
			$this->transactionCounter++;
			return $this->dbh->beginTransaction();
		}
		$this->transactionCounter++;
		return $this->transactionCounter >= 0;
	}

	/**
	 * Commit a transaction
	 *
	 * If the transaction counter is zero, commit the transaction otherwise
	 * decrement the transaction counter. This method is useful in nested
	 * transactions.
	 * @return bool True on success, false on failure.
	 */
	protected function transactionCommit() {
		$this->transactionCounter--;
		if ( $this->transactionCounter === 0 ) {
			return $this->dbh->commit();
		}
		return $this->transactionCounter >= 0;
	}

	/**
	 * Rollback a transaction
	 *
	 * If the transaction counter is greater than 0, set it to
	 * 0 and rollback the transaction. This method is useful in nested
	 * transactions.
	 * @return bool True on success, false on failure.
	 */
	protected function transactionRollback() {
		if ( $this->transactionCounter >= 0 ) {
			$this->transactionCounter = 0;
			return $this->dbh->rollback();
		}
		$this->transactionCounter = 0;
		return false;
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
	 * @param PDOStatement $stmt Previously prepared statement
	 * @param array $values Values to bind
	 */
	protected function bind( $stmt, $values ) {
		$values = $values ?: [];

		if ( count( array_filter( array_keys( $values ), 'is_string' ) ) ) {
			// associative array provided
			foreach ( $values as $key => $value ) {
				// infer bind type from key prefix
				[ $prefix, ] = explode( '_', "{$key}_", 2 );

				switch ( $prefix ) {
					case 'int':
						$type = PDO::PARAM_INT;
						break;
					case 'bool':
						$type = PDO::PARAM_BOOL;
						break;
					case 'null':
						$type = PDO::PARAM_NULL;
						break;
					default:
						$type = PDO::PARAM_STR;
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
	 * @param array|null $params Prepared statement parameters
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
	 * @param array|null $params Prepared statement parameters
	 * @return array Result rows
	 */
	protected function fetchAll( $sql, $params = null ) {
		$this->logger->debug( $sql, $params ?: [] );
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
	 * @param array|null $params Prepared statement parameters
	 * @return stdClass StdClass with rows and found members
	 */
	protected function fetchAllWithFound( $sql, $params = null ) {
		$ret = new stdClass;
		$ret->rows = $this->fetchAll( $sql, $params );

		$ret->found = $this->fetch( 'SELECT FOUND_ROWS() AS found' );
		$ret->found = $ret->found['found'];

		return $ret;
	}

	/**
	 * Prepare and execute an SQL statement in a transaction.
	 *
	 * @param string $sql SQL
	 * @param array|null $params Prepared statement parameters
	 * @return bool False if an exception was generated, true otherwise
	 */
	protected function update( $sql, $params = null ) {
		$stmt = $this->dbh->prepare( $sql );
		try {
			$this->transactionStart();
			$stmt->execute( $params );
			$this->transactionCommit();
			return true;

		} catch ( PDOException $e ) {
			$this->transactionRollback();
			$this->logger->error( 'Update failed.', [
				'method' => __METHOD__,
				'exception' => $e,
				'sql' => $sql,
				'params' => $params,
			] );
			return false;
		}
	}

	/**
	 * Prepare and execute an SQL statement in a transaction.
	 *
	 * @param string $sql SQL
	 * @param array|null $params Prepared statement parameters
	 * @return string|false Last insert id or false if an exception was generated
	 */
	protected function insert( $sql, $params = null ) {
		$stmt = $this->dbh->prepare( $sql );
		try {
			$this->transactionStart();
			$stmt->execute( $params );
			$rowid = $this->dbh->lastInsertId();
			$this->transactionCommit();
			return $rowid;

		} catch ( PDOException $e ) {
			$this->transactionRollback();
			$this->logger->error( 'Insert failed.', [
				'method' => __METHOD__,
				'exception' => $e,
				'sql' => $sql,
				'params' => $params,
			] );
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
		return static::buildBooleanClause( 'WHERE', $where, $conjunction );
	}

	/**
	 * Construct a having clause.
	 * @param array $having List of conditions
	 * @param string $conjunction Joining operation ('and' or 'or')
	 * @return string Having clause or empty string
	 */
	protected static function buildHaving(
		array $having, $conjunction = 'AND'
	) {
		return static::buildBooleanClause( 'HAVING', $having, $conjunction );
	}

	/**
	 * Construct a boolean clause.
	 * @param string $type Clause type (eg 'WHERE', 'HAVING')
	 * @param array $expressions List of expressions
	 * @param string $conjunction Joining operation ('AND' or 'OR')
	 * @return string Clause or empty string
	 */
	protected static function buildBooleanClause(
		$type, array $expressions, $conjunction = 'AND'
	) {
		if ( $expressions ) {
			return "{$type} (" .
				implode( ") {$conjunction} (", $expressions ) .
				') ';
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
		$args = [];
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
		return array_map( static function ( $elm ) {
			return ":{$elm}";
		}, $list );
	}
}
