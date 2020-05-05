<?php


namespace ShardMatrix\DB;

use mysql_xdevapi\RowResult;
use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\GoThreaded\NodeResult;
use ShardMatrix\Node;
use ShardMatrix\Nodes;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Uuid;

/**
 * Class ShardMatrixStatement
 * @package ShardMatrix\DB
 */
class ShardMatrixStatement implements ResultsInterface {
	/**
	 * @var \PDOStatement|null
	 */
	protected ?\PDOStatement $pdoStatement = null;
	/**
	 * @var string|null
	 */
	protected ?string $queryString = null;
	/**
	 * @var Node|null
	 */
	protected ?Node $node = null;
	/**
	 * @var Uuid|null
	 */
	protected ?Uuid $uuid = null;
	/**
	 * @var array
	 */
	protected array $data = [];
	/**
	 * @var bool
	 */
	protected bool $dataSuccess = false;
	/**
	 * @var bool|null
	 */
	protected ?bool $successChecked = null;
	/**
	 * @var Uuid|null
	 */
	protected ?Uuid $lastInsertUuid = null;
	/**
	 * @var string
	 */
	protected string $dataRowReturnClass;

	/**
	 * ShardMatrixStatement constructor.
	 *
	 * @param \PDOStatement|null $pdoStatement
	 * @param Node|null $node
	 * @param Uuid|null $uuid
	 * @param string $dataRowReturnClass
	 */
	public function __construct( ?\PDOStatement $pdoStatement, ?Node $node, ?Uuid $uuid, string $dataRowReturnClass = DataRow::class ) {
		$this->uuid               = $uuid;
		$this->node               = $node;
		$this->pdoStatement       = $pdoStatement;
		$this->dataRowReturnClass = $dataRowReturnClass;
	}

	/**
	 * @return \PDOStatement|null
	 */
	public function getPdoStatement(): ?\PDOStatement {
		return $this->pdoStatement;
	}

	/**
	 * @return Node|null
	 */
	public function getNode(): ?Node {
		return $this->node;
	}

	/**
	 * @return Uuid|null
	 */
	public function getUuid(): ?Uuid {
		return $this->uuid;
	}

	/**
	 * @return array
	 */
	public function fetchAllArrays(): array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				$this->dataSuccess = true;

				return $this->pdoStatement->fetchAll( \PDO::FETCH_ASSOC );
			}
		}
		if ( $this->data ) {
			return $this->data;
		}

		return [];
	}

	/**
	 * @return array
	 */
	public function fetchAllObjects(): array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				if ( $this->isSelectQuery() ) {
					return $this->pdoStatement->fetchAll( \PDO::FETCH_OBJ );
				}
			}
		}
		if ( $this->data ) {
			$returnArray = [];
			foreach ( $this->data as $data ) {
				$returnArray[] = (object) $data;
			}

			return $returnArray;
		}

		return [];
	}

	/**
	 * @return array|null
	 */
	public function fetchRowArray(): array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				if ( $this->isSelectQuery() ) {
					return $this->pdoStatement->fetch( \PDO::FETCH_ASSOC );
				}
			}
		}
		if ( $this->data && isset( $this->data[0] ) ) {
			return $this->data[0];
		}

		return [];
	}

	/**
	 * @return \stdClass|null
	 */
	public function fetchRowObject(): ?\stdClass {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				if ( $this->isSelectQuery() ) {
					return $this->pdoStatement->fetch( \PDO::FETCH_OBJ );
				}

			}
		}
		if ( $this->data && isset( $this->data[0] ) ) {
			return (object) $this->data[0];
		}

		return null;
	}

	/**
	 * @return int
	 */
	public function rowCount(): int {
		if ( $this->pdoStatement ) {
			return $this->pdoStatement->rowCount();
		}

		return count( $this->data );
	}

	public function __preSerialize() {
		$this->data         = $this->fetchAllArrays();
		$this->queryString  = $this->getPdoStatement()->queryString;
		$this->pdoStatement = null;

	}

	/**
	 * @return DataRows
	 */
	public function fetchDataRows(): DataRows {
		$resultSet = new DataRows( [], $this->dataRowReturnClass );
		if ( $results = $this->fetchAllObjects() ) {
			$resultSet->setDataRows( $results, $this->getDataRowReturnClass() );
		}

		return $resultSet;
	}

	/**
	 * @return ShardDataRowInterface|null
	 */
	public function fetchDataRow(): ?ShardDataRowInterface {
		if ( $row = $this->fetchRowObject() ) {
			$returnClass = $this->dataRowReturnClass;

			return ( new DataRowFactory( $row, $returnClass ) )->create();
		}

		return null;
	}

	/**
	 * @param bool|null $successChecked
	 */
	public function setSuccessChecked( ?bool $successChecked ): ShardMatrixStatement {
		$this->successChecked = $successChecked;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSuccessful(): bool {
		$success = false;
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				$success = true;
			}
		} else {
			$success = $this->dataSuccess;
		}

		if ( is_bool( $this->successChecked ) && $success ) {
			return $this->successChecked;
		}

		return $success;

	}

	/**
	 * @return Uuid|null
	 */
	public function getLastInsertUuid(): ?Uuid {

		if ( $this->lastInsertUuid && $this->lastInsertUuid->isValid() ) {
			return $this->lastInsertUuid;
		}

		return null;
	}

	/**
	 * @param Uuid|null $lastInsertUuid
	 *
	 * @return $this
	 */
	public function setLastInsertUuid( Uuid $lastInsertUuid ): ShardMatrixStatement {
		$this->lastInsertUuid = $lastInsertUuid;

		return $this;
	}

	/**
	 * @return Nodes
	 */
	public function getOtherTableNodes(): Nodes {
		$nodes = [];
		if ( $this->getUuid() ) {

			foreach ( $this->getAllTableNodes() as $node ) {
				if ( $node->getName() != $this->getNode()->getName() ) {
					$nodes[] = $node;
				}
			}

		}

		return new Nodes( $nodes );
	}

	/**
	 * @return Nodes
	 */
	public function getAllTableNodes(): Nodes {
		return ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $this->getUuid()->getTable()->getName(), false );
	}

	/**
	 * @return string
	 */
	public function getDataRowReturnClass(): string {
		return $this->dataRowReturnClass;
	}

	/**
	 * @return string|null
	 */
	public function getQueryString(): ?string {
		if ( $this->getPdoStatement() ) {
			return $this->getPdoStatement()->queryString;
		}

		return $this->queryString;
	}

	/**
	 * @return bool
	 */
	public function isSelectQuery(): bool {
		return strpos( strtolower( trim( $this->getQueryString() ) ), 'select' ) === 0;
	}

	/**
	 * @return bool
	 */
	public function isUpdateQuery(): bool {
		return strpos( strtolower( trim( $this->getQueryString() ) ), 'update' ) === 0;
	}

	/**
	 * @return bool
	 */
	public function isInsertQuery(): bool {
		return strpos( strtolower( trim( $this->getQueryString() ) ), 'insert' ) === 0;
	}

	/**
	 * @return bool
	 */
	public function isDeleteQuery(): bool {
		return strpos( strtolower( trim( $this->getQueryString() ) ), 'delete' ) === 0;
	}

	/**
	 * @param string $column
	 * @param string|null $groupByColumn
	 *
	 * @return int
	 */
	public function sumColumn( string $column ): float {
		$sum = 0;
		foreach ( $this->fetchDataRows() as $row ) {
			if ( isset( $row->__toObject()->$column ) && is_numeric( $row->__toObject()->$column ) ) {
				$sum = $sum + $row->__toObject()->$column;
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
				$sum = $sum + $row->__toObject()->$column;
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
	 * @param NodeResult $nodeResult
	 */
	public function setDataFromGoThreadedResult( NodeResult $nodeResult ) {
		$this->data = $nodeResult->getData();
	}


}