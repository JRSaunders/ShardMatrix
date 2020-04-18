<?php


namespace ShardMatrix\DB;

use ShardMatrix\Uuid;

class ShardMatrixStatements implements \Iterator {

	protected $position = 0;

	protected array $shardMatrixStatements = [];
	protected ?string $orderByColumn = null;
	protected ?string $orderByDirection = null;

	/**
	 * ShardMatrixStatements constructor.
	 *
	 * @param array $shardMatrixStatements
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 */
	public function __construct( array $shardMatrixStatements, ?string $orderByColumn = null, ?string $orderByDirection = null ) {
		$this->shardMatrixStatements = $shardMatrixStatements;
		$this->orderByColumn         = $orderByColumn;
		$this->orderByDirection      = $orderByDirection;
	}

	public function countShardMatrixStatements(): int {
		return count( $this->getShardMatrixStatements() );
	}


	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return ShardMatrixStatement Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		return $this->getShardMatrixStatements()[ $this->position ];
	}

	/**
	 * Move forward to next element
	 * @link https://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * Return the key of the current element
	 * @link https://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link https://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind() {
		$this->position = 0;
	}


	public function valid() {
		return isset( $this->shardMatrixStatements[ $this->position ] );
	}

	private function orderResults( &$results, bool $row = false ) {
		if ( $this->orderByColumn && count( $results ) ) {
			usort( $results, function ( $a, $b ) {
				$orderByColumn = $this->orderByColumn;
				if ( ! $a instanceof \stdClass ) {
					$a = (object) $a;
				}
				if ( ! $b instanceof \stdClass ) {
					$b = (object) $b;
				}
				if ( is_string( $this->orderByDirection ) && strtolower( $this->orderByDirection ) == 'desc' ) {
					return ( $a->$orderByColumn > $b->$orderByColumn ) ? - 1 : + 1;
				}
				/**
				 * TODO Deal with dates and string better
				 */
				//strnatcmp()
				return ( $a->$orderByColumn < $b->$orderByColumn ) ? + 1 : - 1;
			} );
		}
		if ( $row && isset( $results[0] ) ) {
			$results = $results[0];
		}

	}

	public function fetchAllArrays(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {

			$results = array_merge( $results, $statement->fetchAllArrays() );
		}
		$this->orderResults( $results );

		return $results;
	}

	public function fetchAllObjects(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$results = array_merge( $results, $statement->fetchAllObjects() );
		}
		$this->orderResults( $results );

		return $results;
	}

	public function fetchRowArray(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$results[] = $statement->fetchRowArray();
		}
		$this->orderResults( $results, true );

		return $results;
	}

	public function fetchRowObject(): ?\stdClass {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$results[] = $statement->fetchRowObject();
		}
		$this->orderResults( $results, true );
		if ( ! $results ) {
			return null;
		}

		return $results;
	}

	/**
	 * @return ShardMatrixStatement[]
	 */
	public function getShardMatrixStatements(): array {
		return $this->shardMatrixStatements;
	}
}