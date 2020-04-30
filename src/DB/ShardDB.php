<?php


namespace ShardMatrix\DB;

use ShardMatrix\Db\Builder\QueryBuilder;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\Nodes;
use ShardMatrix\PdoCacheInterface;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Table;
use ShardMatrix\Uuid;

/**
 * Class ShardDB
 * @package ShardMatrix\DB
 */
class ShardDB {

	/**
	 *
	 * @var string
	 */
	protected $defaultDataRowClass = DataRow::class;
	/**
	 * @var array
	 */
	protected $dataRowClasses = [];
	/**
	 * @var \Closure|null
	 */
	protected ?\Closure $checkSuccessFunction = null;
	/**
	 * @var PdoCacheInterface|null
	 */
	protected ?PdoCacheInterface $pdoCache = null;

	/**
	 * @param string $tableName
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 * @throws \ShardMatrix\Exception
	 */
	public function insert( string $tableName, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		$node = NodeDistributor::getNode( $tableName );
		$uuid = Uuid::make( $node, new Table( $tableName ) );

		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( new PreStatement( $node, $sql, $bind, $uuid, null, __METHOD__ ) );

	}


	/**
	 * @param string $tableName
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 * @throws \ShardMatrix\Exception
	 */
	public function newNodeInsert( string $tableName, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		NodeDistributor::clearGroupNodes();

		return $this->insert( $tableName, $sql, $bind );
	}

	/**
	 * @param Uuid $uuid
	 * @param string $sql
	 * @param $bind
	 *
	 * @return ShardDB
	 */
	private function uuidBind( Uuid $uuid, string $sql, &$bind ): ShardDB {
		if ( strpos( $sql, ':uuid ' ) !== false || strpos( $sql, ':uuid,' ) !== false || strpos( $sql, 'uuid = :uuid' ) !== false ) {
			$bind[':uuid'] = $uuid->toString();
		}

		return $this;
	}

	/**
	 * @param Uuid $uuid
	 * @param string $sql
	 * @param array|null $bind
	 * @param ShardDataRowInterface|null $dataRow
	 *
	 * @return ShardMatrixStatement|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	public function uuidUpdate( Uuid $uuid, string $sql, ?array $bind = null, ?ShardDataRowInterface $dataRow = null ): ?ShardMatrixStatement {
		NodeDistributor::setFromUuid( $uuid );

		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( new PreStatement( $uuid->getNode(), $sql, $bind, $uuid, $dataRow, __METHOD__ ) );

	}

	/**
	 * @param Uuid $uuid
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 */
	public function uuidInsert( Uuid $uuid, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( new PreStatement( $uuid->getNode(), $sql, $bind, $uuid, null, __METHOD__ ) );
	}

	/**
	 * @param Uuid $uuid
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 */
	public function uuidQuery( Uuid $uuid, string $sql, ?array $bind = null ): ?ShardMatrixStatement {

		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( new PreStatement( $uuid->getNode(), $sql, $bind, $uuid, null, __METHOD__ ) );
	}

	/**
	 * @param Uuid|null $uuid
	 *
	 * @return DataRow|null
	 */
	public function getByUuid( ?Uuid $uuid ): ?DataRow {
		if ( ! $uuid ) {
			return null;
		}

		return $this->uuidQuery(
			$uuid,
			"select * from {$uuid->getTable()->getName()} where uuid = :uuid limit 1;"
		)->fetchDataRow();
	}

	/**
	 * @param Uuid $uuid
	 *
	 * @return DataRow|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	protected function getByUuidSeparateConnection( Uuid $uuid ): ?DataRow {
		return $this
			->uuidBind( $uuid, $sql = "select * from {$uuid->getTable()->getName()} where uuid = :uuid limit 1;", $bind )
			->execute( new PreStatement( $uuid->getNode(), $sql, $bind, $uuid, null, __METHOD__ ), true )->fetchDataRow();
	}


	/**
	 * @param Uuid $uuid
	 *
	 * @return bool
	 */
	public function deleteByUuid( Uuid $uuid ): bool {

		return $this->uuidQuery(
			$uuid,
			"delete from {$uuid->getTable()->getName()} where uuid = :uuid limit 1;"
		)->isSuccessful();
	}

	/**
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 */
	public function nodeQuery( Node $node, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		return $this->execute( new PreStatement( $node, $sql, $bind, null, null, __METHOD__ ) );
	}

	/**
	 * @param Nodes $nodes
	 * @param string $sql
	 * @param array|null $bind
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 * @param string|null $calledMethod
	 *
	 * @return ShardMatrixStatements|null
	 */
	public function nodesQuery( Nodes $nodes, string $sql, ?array $bind = null, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null ): ?ShardMatrixStatements {
		$nodeQueries = [];
		foreach ( $nodes as $node ) {
			$nodeQueries[] = new NodeQuery( $node, $sql, $bind );
		}

		return $this->nodeQueries( new NodeQueries( $nodeQueries ), $orderByColumn, $orderByDirection, $calledMethod ?? __METHOD__ );
	}

	/**
	 * @param NodeQueries $nodeQueries
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 * @param string|null $calledMethod
	 *
	 * @return ShardMatrixStatements|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	public function nodeQueries( NodeQueries $nodeQueries, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null ): ?ShardMatrixStatements {

		$queryPidUuid = uniqid( getmypid() . '-' );
		$pids         = [];

		foreach ( $nodeQueries as $nodeQuery ) {
			$pid = pcntl_fork();
			Connections::closeConnections();
			if ( $pid == - 1 ) {
				die( 'could not fork' );
			} else if ( $pid ) {

				$pids[] = $pid;

			} else {
				$stmt = $this->execute( new PreStatement( $nodeQuery->getNode(), $nodeQuery->getSql(), $nodeQuery->getBinds(), null, null, $calledMethod ?? __METHOD__ ) );
				if ( $stmt ) {
					$stmt->__preSerialize();
				}
				$this->getPdoCache()->write( $queryPidUuid . '-' . getmypid(), $stmt );
				exit;
			}
		}


		while ( count( $pids ) > 0 ) {
			foreach ( $pids as $key => $pid ) {
				$res = pcntl_waitpid( $pid, $status, WNOHANG );
				// If the process has already exited
				if ( $res == - 1 || $res > 0 ) {
					unset( $pids[ $key ] );
				}
			}
			usleep( 10000 );
		}

		$results = $this->getPdoCache()->scanAndClean( $queryPidUuid . '-' );

		if ( $results ) {
			return new ShardMatrixStatements( $results, $orderByColumn, $orderByDirection );
		}

		return null;
	}

	/**
	 * @param string $tableName
	 * @param string $sql
	 * @param array|null $bind
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 *
	 * @return ShardMatrixStatements|null
	 */
	public function allNodesQuery(
		string $tableName, string $sql, ?array $bind = null, ?string $orderByColumn = null, ?string $orderByDirection = null
	): ?ShardMatrixStatements {

		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $tableName, false );

		return $this->nodesQuery( $nodes, $sql, $bind, $orderByColumn, $orderByDirection, __METHOD__ );
	}


	/**
	 * @param PreStatement $preStatement
	 * @param bool $useNewConnection
	 * @param bool $rollbacks
	 *
	 * @return ShardMatrixStatement|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	public function execute( PreStatement $preStatement, bool $useNewConnection = false, bool $rollbacks = false ): ?ShardMatrixStatement {
		$node         = $preStatement->getNode();
		$sql          = $preStatement->getSql();
		$bind         = $preStatement->getBind();
		$uuid         = $preStatement->getUuid();
		$calledMethod = $preStatement->getCalledMethod();
		try {
			$this->preExecuteProcesses( $preStatement );
		} catch ( \Exception $exception ) {
			if ( $exception instanceof DuplicateException ) {
				throw $exception;
			}
			throw new Exception( $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
		}
		$db = Connections::getNodeConnection( $node, $useNewConnection );

		if ( $rollbacks ) {
			$db->beginTransaction();
		}
		try {
			$stmt = $db->prepare( $sql );
			$stmt->execute( $bind );

			if ( $stmt ) {
				if ( $uuid ) {
					$node->setLastUsedTableName( $uuid->getTable()->getName() );
				}
				$shardStmt = new ShardMatrixStatement( $stmt, $node, $uuid, $this->getDataRowClassByNode( $node ) );
				if ( strpos( strtolower( trim( $sql ) ), 'insert ' ) === 0 ) {
					if ( $stmt->rowCount() > 0 && $uuid ) {
						$shardStmt->setLastInsertUuid( $uuid );
					}
				}
				$this->postExecuteProcesses( $shardStmt, $preStatement );

				$shardStmt->setSuccessChecked( $this->executeCheckSuccessFunction( $shardStmt, $calledMethod ) );
				if ( $rollbacks ) {
					$db->commit();
				}

				return $shardStmt;
			}

		} catch ( DuplicateException | \PDOException $exception ) {
			if ( $rollbacks ) {
				$db->rollBack();
			}
			if ( $exception instanceof DuplicateException ) {
				throw $exception;
			}

			throw new Exception( $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
		}

		return null;
	}

	/**
	 * @param \Closure|null $checkSuccessFunction
	 *
	 * @return $this|ShardMatrix
	 */
	public function setCheckSuccessFunction( ?\Closure $checkSuccessFunction ): ShardDB {
		$this->checkSuccessFunction = $checkSuccessFunction;

		return $this;
	}

	/**
	 * @param ShardMatrixStatement $statement
	 * @param string $calledMethod
	 *
	 * @return bool|null
	 */
	private function executeCheckSuccessFunction( ShardMatrixStatement $statement, string $calledMethod ): ?bool {
		if ( $this->checkSuccessFunction ) {
			return call_user_func_array( $this->checkSuccessFunction, [
				$statement,
				str_replace( static::class . '::', '', $calledMethod )
			] );
		}

		return null;
	}

	/**
	 * @param Node $node
	 *
	 * @return string
	 */
	private function getDataRowClassByNode( Node $node ): string {

		if ( $node->getLastUsedTableName() && isset( $this->getDataRowClasses()[ $node->getLastUsedTableName() ] ) ) {
			return $this->getDataRowClasses()[ $node->getLastUsedTableName() ];
		}

		return $this->getDefaultDataRowClass();
	}

	/**
	 * @param string $defaultDataRowClass
	 *
	 * @return ShardDB
	 */
	public function setDefaultDataRowClass( string $defaultDataRowClass ): ShardDB {
		$this->defaultDataRowClass = $defaultDataRowClass;

		return $this;
	}

	/**
	 * @return string
	 */
	private function getDefaultDataRowClass(): string {
		return $this->defaultDataRowClass;
	}

	private function preExecuteProcesses( PreStatement $preStatement ) {

		$calledMethod = str_replace( static::class . '::', '', $preStatement->getCalledMethod() );

		switch ( $calledMethod ) {
			case 'uuidUpdate':
			case 'update':
				$dataRow = $preStatement->getDataRow() ?? $this->getByUuid( $preStatement->getUuid() );
				if ( $dataRow ) {
					$this->handleDuplicateColumns( $dataRow, function ( ShardDataRowInterface $dataRow, array $columnsIssue ) {
						$columnsIssueString = '';
						if ( $columnsIssue ) {
							foreach ( $columnsIssue as $key => $val ) {
								$columnsIssueString .= ' - ( Column:' . $key . ' = ' . $val . ' ) ';
							}
						}

						throw new DuplicateException( $columnsIssue, 'Update Duplicate Column violation ' . $columnsIssueString, 45 );
					} );
				}
				break;
		}
	}

	/**
	 * @param ShardMatrixStatement $statement
	 * @param PreStatement $preStatement
	 */
	private function postExecuteProcesses( ShardMatrixStatement $statement, PreStatement $preStatement ) {
		$calledMethod = str_replace( static::class . '::', '', $preStatement->getCalledMethod() );
		switch ( $calledMethod ) {
			case 'insert':
			case 'uuidInsert':
				$dataRow = $preStatement->getDataRow() ?? $this->getByUuid( $preStatement->getUuid() );
				if ( $dataRow ) {
					$this->handleDuplicateColumns( $dataRow, function ( ShardDataRowInterface $dataRow, array $columnsIssue ) {
						$handleNote = '';
						if ( $this->deleteByUuid( $dataRow->getUuid() ) ) {
							$handleNote = '( Record Removed ' . $dataRow->getUuid()->toString() . ' )';
						}
						$columnsIssueString = '';
						if ( $columnsIssue ) {
							foreach ( $columnsIssue as $key => $val ) {
								$columnsIssueString .= ' - ( Column:' . $key . ' = ' . $val . ' ) ';
							}
						}
						throw new DuplicateException( $columnsIssue, 'Insert Duplicate Column violation ' . $columnsIssueString . $handleNote, 46 );
					} );
				}
				break;
		}

	}

	/**
	 * @param ShardDataRowInterface $dataRow
	 * @param \Closure $handleDuplicateColumns
	 */
	protected function handleDuplicateColumns( ShardDataRowInterface $dataRow, \Closure $handleDuplicateColumns ): void {

		if ( ! ( $uuid = $dataRow->getUuid() ) ) {
			return;
		}

		if ( $uniqueColumns = $dataRow->getUuid()->getTable()->getUniqueColumns() ) {
			$sqlArray      = [];
			$selectColumns = [];
			foreach ( $uniqueColumns as $column ) {
				if ( $dataRow->__columnIsset( $column ) ) {
					$binds[":{$column}"] = $dataRow->$column;
					$selectColumns[]     = $column;
					$sqlArray[]          = " {$column} = :{$column} ";
				}
			}
			$sql            = "select " . join( ', ', $selectColumns ) . " from {$dataRow->getUuid()->getTable()->getName()} where";
			$sql            = $sql . " ( " . join( 'or', $sqlArray ) . " ) and uuid != :uuid limit 1;";
			$binds[':uuid'] = $uuid->toString();
			$nodesResults   = $this->allNodesQuery( $uuid->getTable()->getName(), $sql, $binds );
			if ( $nodesResults && $nodesResults->isSuccessful() ) {
				$columnsIssue = [];
				foreach ( $nodesResults->fetchDataRows() as $row ) {
					foreach ( $selectColumns as $column ) {
						if ( $dataRow->$column == $row->$column ) {
							$columnsIssue[ $column ] = $dataRow->$column;
						}
					}
				}

				$handleDuplicateColumns( $dataRow, $columnsIssue );

			}
		}
	}

	/**
	 * @param array $dataRowClasses
	 *
	 * @return $this
	 */
	public function setDataRowClasses( array $dataRowClasses ): ShardDB {
		$this->dataRowClasses = $dataRowClasses;

		return $this;
	}

	/**
	 * @return array
	 */
	private function getDataRowClasses(): array {
		return $this->dataRowClasses;
	}


	public function paginationByQueryBuilder( QueryBuilder $queryBuilder, int $pageNumber = 1, int $perPage = 15, ?int $limitPages = null ) {

		$paginationQuery    = clone( $queryBuilder );
		$uuidOrderDirection = null;
		if ( $paginationQuery->orders ) {
			foreach ( $paginationQuery->orders as $order ) {
				if ( $order['column'] == 'uuid' ) {
					$uuidOrderDirection = $order['direction'];
				}
			}
		}
		if ( ! $uuidOrderDirection ) {
			$uuidOrderDirection = $paginationQuery->getPrimaryOrderDirection() ?? 'asc';
			$paginationQuery->orderBy( 'uuid', $uuidOrderDirection );
		}

		$paginationQuery->select( [ 'uuid' ] );
		if ( $limitPages ) {
			$paginationQuery->limit( $perPage * $limitPages );
		} else {
			$paginationQuery->limit      = null;
			$paginationQuery->unionLimit = null;
		}
		$queryHash = 'pag-' . md5( $paginationQuery->toSql() . join( '', $paginationQuery->getBindings() ) . '--' . $perPage );

		$pageMarkers = $this->getPdoCache()->read( $queryHash );

		if ( ! $pageMarkers ) {
			$pageMarkers = [];
			$stmt        = $paginationQuery->getStatement( [ 'uuid' ] );

			$stmt->fetchAllObjects();
			$pages        = 0;
			$objectsCount = $stmt->rowCount();
			$results      = $stmt->fetchAllObjects();
			for ( $i = 0; $i < $objectsCount; $i ++ ) {
				if ( $i % $perPage == 0 ) {
					$pages ++;
					$pageMarkers[] = $results[ $i ];
				}
				if ( $limitPages && $pages >= $limitPages ) {
					break;
				}
			}
			$this->getPdoCache()->write( $queryHash, $pageMarkers );
		}

	}

	/**
	 * @return PdoCacheInterface
	 */
	protected function getPdoCache(): PdoCacheInterface {
		if ( isset( $this->pdoCache ) ) {
			return $this->pdoCache;
		}
		$cacheClass = ShardMatrix::getPdoCacheClass();

		return $this->pdoCache = new $cacheClass();
	}

	public function __destruct() {
		$this->getPdoCache()->runCleanPolicy( $this );
	}


}