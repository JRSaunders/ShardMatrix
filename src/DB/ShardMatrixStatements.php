<?php


namespace ShardMatrix\DB;

use mysql_xdevapi\RowResult;
use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\Uuid;

/**
 * Class ShardMatrixStatements
 * @package ShardMatrix\DB
 */
class ShardMatrixStatements implements \Iterator, ResultsInterface {

	/**
	 * @var bool
	 */
	protected bool $fromCache = false;
	/**
	 * @var int
	 */
	protected $position = 0;
	/**
	 * @var ShardMatrixStatement[]
	 */
	protected array $shardMatrixStatements = [];
	/**
	 * @var string|null
	 */
	protected ?string $orderByColumn = null;
	/**
	 * @var string|null
	 */
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

	/**
	 * @return int
	 */
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

	/**
	 * @return bool
	 */
	public function valid() {
		return isset( $this->shardMatrixStatements[ $this->position ] );
	}

	/**
	 * @param $results
	 * @param bool $row
	 */
	private function orderResults( &$results, bool $row = false ) {

		if ( $this->orderByColumn && count( $results ) > 1 ) {
			usort( $results, function ( $a, $b ) {
				$orderByColumn = $this->orderByColumn;
				if ( ! $a instanceof \stdClass ) {
					$a = (object) $a;
				}
				if ( ! $b instanceof \stdClass ) {
					$b = (object) $b;
				}
				if ( is_string( $this->orderByDirection ) && strtolower( $this->orderByDirection ) == 'desc' ) {

					return strcmp( strtolower( $b->$orderByColumn ), strtolower( $a->$orderByColumn ) );
				}
				if ( is_string( $this->orderByDirection ) && strtolower( $this->orderByDirection ) == 'asc' ) {
					return strcmp( strtolower( $a->$orderByColumn ), strtolower( $b->$orderByColumn ) );
				}

			} );
		}
		if ( $row && isset( $results[0] ) ) {
			$results = $results[0];
		}

	}


	/**
	 * @return array
	 */
	public function fetchAllArrays(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {

			$results = array_merge( $results, $statement->fetchAllArrays() );
		}
		$this->orderResults( $results );

		return $results;
	}

	/**
	 * @return array
	 */
	public function fetchAllObjects(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$results = array_merge( $results, $statement->fetchAllObjects() );
		}
		$this->orderResults( $results );

		return $results;
	}

	/**
	 * @return array
	 */
	public function fetchRowArray(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			if ( $row = $statement->fetchRowArray() ) {
				$results[] = $row;
			}

		}
		$this->orderResults( $results, true );

		return $results;
	}

	/**
	 * @return \stdClass|null
	 */
	public function fetchRowObject(): ?\stdClass {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			if ( $row = $statement->fetchRowObject() ) {
				$results[] = $row;
			}
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

	/**
	 * @return DataRows
	 */
	public function fetchDataRows(): DataRows {
		$class = DataRow::class;
		if ( isset( $this->getShardMatrixStatements()[0] ) ) {
			$class = $this->getShardMatrixStatements()[0]->getDataRowReturnClass();
		}
		$resultSet = new DataRows( [], $class );
		if ( $results = $this->fetchAllObjects() ) {
			$resultSet->setDataRows( $results, $class );
		}

		return $resultSet;
	}

	/**
	 * @return ShardDataRowInterface|null
	 */
	public function fetchDataRow(): ?ShardDataRowInterface {
		if ( $row = $this->fetchRowObject() ) {
			$class = DataRow::class;
			if ( isset( $this->getShardMatrixStatements()[0] ) ) {
				$class = $this->getShardMatrixStatements()[0]->getDataRowReturnClass();
			}

			return ( new DataRowFactory( $row, $class ) )->create();
		}

		return null;
	}

	/**
	 * @return bool
	 */
	public function isSuccessful(): bool {
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			if ( $statement->isSuccessful() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * @return int
	 */
	public function rowCount(): int {
		$count = 0;
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$count = $count + $statement->rowCount();
		}

		return $count;
	}

	/**
	 * @return Uuid[]
	 */
	public function getLastInsertUuids(): array {
		$results = [];
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			if ( $uuid = $statement->getLastInsertUuid() ) {
				$results[] = $uuid;
			}
		}

		return $results;
	}


	/**
	 * @param string $column
	 *
	 * @return int
	 */
	public function sumColumn( string $column ): float {
		$sum = 0;
		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
				$sum = $sum + $row->$column;
			}
		}

		return $sum;
	}

	/**
	 * @param string $column
	 *
	 * @return float
	 */
	public function avgColumn( string $column ): float {
		$sum = 0;
		$i   = 0;
		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
				$i ++;
				$sum = $sum + $row->$column;
			}
		}

		if ( $i == 0 ) {
			return 0;
		}

		return $sum / $i;
	}

	/**
	 * @param string $column
	 *
	 * @return float|null
	 */
	public function minColumn( string $column ): ?float {
		$result = null;
		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
				if ( ! isset( $result ) ) {
					$result = $row->__toObject()->$column;
				}
				if ( $result > $row->__toObject()->$column ) {
					$result = $row->__toObject()->$column;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $column
	 *
	 * @return float|null
	 */
	public function maxColumn( string $column ): ?float {
		$result = null;

		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
				if ( ! isset( $result ) ) {
					$result = $row->__toObject()->$column;
				}
				if ( $result < $row->__toObject()->$column ) {
					$result = $row->__toObject()->$column;
				}
			}
		}

		return $result;
	}

	/**
	 * @param string $column
	 * @param string $groupByColumn
	 *
	 * @return GroupSums
	 */
	public function sumColumnByGroup( string $column, string $groupByColumn ): GroupSums {
		$sum = [];
		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$groupByColumn ) ) {
				if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
					if ( ! isset( $sum[ $row->__toObject()->$groupByColumn ] ) ) {
						$sum[ $row->__toObject()->$groupByColumn ] = 0;
					}
					$sum[ $row->__toObject()->$groupByColumn ] = $sum[ $row->__toObject()->$groupByColumn ] + $row->__toObject()->$column;
				}
			}
		}

		$results = [];
		foreach ( $sum as $group => $result ) {
			$results[] = new GroupSum( (object) [ 'column' => $group, 'sum' => $result ] );
		}

		return new GroupSums( $results );

	}

	/**
	 * @return Uuid|null
	 */
	public function getLastInsertUuid(): ?Uuid {
		if ( $results = $this->getLastInsertUuids() ) {
			return end( $results );
		}

		return null;
	}

	/**
	 * @param string|null $orderByColumn
	 *
	 * @return ShardMatrixStatements
	 */
	public function setOrderByColumn( ?string $orderByColumn ): ShardMatrixStatements {
		$this->orderByColumn = $orderByColumn;

		return $this;
	}

	/**
	 * @param string|null $orderByDirection
	 *
	 * @return ShardMatrixStatements
	 */
	public function setOrderByDirection( ?string $orderByDirection ): ShardMatrixStatements {
		$this->orderByDirection = $orderByDirection;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isFromCache(): bool {
		return $this->fromCache;
	}

	/**
	 * @param bool $fromCache
	 */
	public function setFromCache( bool $fromCache = true ): void {
		foreach ( $this->getShardMatrixStatements() as $statement ) {
			$statement->setFromCache( $fromCache );
		}
		$this->fromCache = $fromCache;
	}
}