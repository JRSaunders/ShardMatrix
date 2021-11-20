<?php


namespace ShardMatrix\DB;

use ShardMatrix\DB\Builder\DB;
use ShardMatrix\DB\Builder\QueryBuilder;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\Exception;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\NodeQueriesAsyncInterface;
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
	public function getByUuid( ?Uuid $uuid ): ?ShardDataRowInterface {
		if ( ! $uuid ) {
			return null;
		}

		return $this->uuidQuery(
			$uuid,
			"select * from {$uuid->getTable()->getName()} where uuid = ? limit 1;",
			[ $uuid->toString() ]
		)->fetchDataRow();
	}

	/**
	 * @param Uuid $uuid
	 *
	 * @return DataRow|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	protected function getByUuidSeparateConnection( Uuid $uuid ): ?ShardDataRowInterface {
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
			"delete from {$uuid->getTable()->getName()} where uuid = ? limit 1;",
			[ $uuid->toString() ]
		)->isSuccessful();
	}

	/**
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 * @param Uuid|null $uuid
	 * @param bool $freshDataOnly
	 *
	 * @return ShardMatrixStatement|null
	 * @throws DuplicateException
	 * @throws Exception
	 */
	public function nodeQuery( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null, $freshDataOnly = false ): ?ShardMatrixStatement {
		return $this->execute( new PreStatement( $node, $sql, $bind, $uuid, null, __METHOD__, $freshDataOnly ) );
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

		$results = $this->getNodeQueriesAsync( $nodeQueries, $orderByColumn, $orderByDirection, $calledMethod ?? __METHOD__ )->getResults();
		if ( $results ) {
			return $results;
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
	 * @param string $geo
	 * @param string $tableName
	 * @param string $sql
	 * @param array|null $bind
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 *
	 * @return ShardMatrixStatements|null
	 */
	public function allNodesGeoQuery(
		string $geo,
		string $tableName,
		string $sql,
		?array $bind = null,
		?string $orderByColumn = null,
		?string $orderByDirection = null
	): ?ShardMatrixStatements {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableNameAndGeo( $tableName, $geo );

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
	public function __execute(
		PreStatement $preStatement,
		bool $useNewConnection = false,
		bool $rollbacks = false
	): ?ShardMatrixStatement {
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

			throw new Exception( $exception->getMessage(), (int) $exception->getCode(), $exception->getPrevious() );
		}

		return null;
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
	public function execute(
		PreStatement $preStatement,
		bool $useNewConnection = false,
		bool $rollbacks = false
	): ?ShardMatrixStatement {
		return ShardCache::execute( $preStatement, $useNewConnection, $rollbacks, $this );
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
	public function getDataRowClassByNode( Node $node ): string {

		if ( $node->getLastUsedTableName() && isset( $this->getDataRowClasses()[ $node->getLastUsedTableName() ] ) ) {
			return $this->getDataRowClasses()[ $node->getLastUsedTableName() ];
		}

		return $this->getDefaultDataRowClass();
	}

	/**
	 * @param string $defaultDataRowClass
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function setDefaultDataRowClass( string $defaultDataRowClass ): ShardDB {
		if ( in_array( ShardDataRowInterface::class, class_implements( $defaultDataRowClass ) ) ) {
			$this->defaultDataRowClass = $defaultDataRowClass;
		} else {
			throw new Exception( $defaultDataRowClass . ' needs to implement ' . ShardDataRowInterface::class );
		}

		return $this;
	}

	/**
	 * @return string
	 */
	private function getDefaultDataRowClass(): string {
		return $this->defaultDataRowClass;
	}

	/**
	 * @param PreStatement $preStatement
	 */
	private function preExecuteProcesses( PreStatement $preStatement ) {

		$calledMethod = str_replace( static::class . '::', '', $preStatement->getCalledMethod() );

		switch ( $calledMethod ) {
			case 'uuidUpdate':
			case 'update':
				$dataRow = $preStatement->getDataRow() ?? $this->getByUuid( $preStatement->getUuid() );
				if ( $dataRow ) {
					$this->handleDuplicateColumns(
						$dataRow,
						function ( ShardDataRowInterface $dataRow, array $columnsIssue ) {
							$columnsIssueString = '';
							if ( $columnsIssue ) {
								foreach ( $columnsIssue as $key => $val ) {
									$columnsIssueString .= ' - ( Column:' . $key . ' = ' . $val . ' ) ';
								}
							}

							throw new DuplicateException( $columnsIssue, 'Update Duplicate Column violation ' .
							                                             $columnsIssueString, 45 );
						}
					);
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
					$this->handleDuplicateColumns(
						$dataRow,
						function ( ShardDataRowInterface $dataRow, array $columnsIssue ) {
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
							throw new DuplicateException( $columnsIssue, 'Insert Duplicate Column violation ' .
							                                             $columnsIssueString . $handleNote, 46 );
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
					$binds[]         = $dataRow->$column;
					$selectColumns[] = $column;
					$sqlArray[]      = " {$column} = ? ";
				}
			}
			if ( $selectColumns ) {
				$sql          = "select " . join( ', ', $selectColumns ) .
				                " from {$dataRow->getUuid()->getTable()->getName()} where";
				$sql          = $sql . " ( " . join( 'or', $sqlArray ) . " ) and uuid != ? limit 1;";
				$binds[]      = $uuid->toString();
				$nodesResults = $this->allNodesQuery( $uuid->getTable()->getName(), $sql, $binds );
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
	}

	/**
	 * @param array $dataRowClasses
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function setDataRowClasses( array $dataRowClasses ): ShardDB {
		foreach ( $dataRowClasses as $class ) {
			if ( ! in_array( ShardDataRowInterface::class, class_implements( $class ) ) ) {
				throw new Exception( $dataRowClasses . ' needs to implement ' . ShardDataRowInterface::class );
			}
		}
		$this->dataRowClasses = $dataRowClasses;

		return $this;
	}

	/**
	 * @return array
	 */
	private function getDataRowClasses(): array {
		return $this->dataRowClasses;
	}

	/**
	 * @param QueryBuilder $queryBuilder
	 * @param int $pageNumber
	 * @param int $perPage
	 * @param int|null $limitPages
	 */
	public function paginationByQueryBuilder(
		QueryBuilder $queryBuilder, int $pageNumber = 1, int $perPage = 15, ?int $limitPages = null
	): PaginationStatement {
		$paginationStatementHash = ( $queryBuilder->from ?? 'na' ) . ':pagstat-' . $queryBuilder->toSqlHash() . '-' . $perPage . '-' . $pageNumber . '-' . $limitPages ?? 0;
		if ( $queryBuilder->isUseCache() ) {
			if ( $cachedPaginationStatement = $this->getPdoCache()->read( $paginationStatementHash ) ) {
				return $cachedPaginationStatement;
			}
		}
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

		if ( $limitPages ) {
			$paginationQuery->limit( $perPage * $limitPages );
		} else {
			$paginationQuery->limit      = null;
			$paginationQuery->unionLimit = null;
		}
		$queryTable = ( $paginationQuery->from ?? 'na' ) . ':';
		$queryHash  = $queryTable . 'pag-' . $paginationQuery->toSqlHash() . '-' . $perPage;


		$markerData = $this->getPdoCache()->read( $queryHash );
		$orders     = $paginationQuery->orders;
		if ( ! $markerData ) {
			$markerData = [];

			$orderColumns = [];
			foreach ( $orders as $order ) {
				$orderColumns[] = $order['column'];
			}
			$concatString = join( ",'-',", $orderColumns );
			$stmt         = $paginationQuery->getStatement( array_merge( [ "uuid" ], [ DB::raw( "LPAD(CONCAT({$concatString}),72,'0') as pag_hash" ) ] ) );
			if ( $stmt instanceof ShardMatrixStatements ) {
				$stmt->setOrderByColumn( 'pag_hash' );
			}
			$stmt->fetchAllObjects();
			$objectsCount = $stmt->rowCount();
			$results      = $stmt->fetchAllObjects();

			for ( $i = 0; $i < $objectsCount; $i ++ ) {
				$markerData[] = $results[ $i ]->uuid;
				if ( ( isset( $limitPages ) ) && ( $i / $perPage > $limitPages ) ) {
					break;
				}
			}
			$this->getPdoCache()->write( $queryHash, $markerData );
		}

		$paginationStatement = new PaginationStatement( $markerData, $pageNumber, $perPage );

		if ( isset( $limitPages ) && $limitPages < $pageNumber ) {
			return $paginationStatement;
		}

		$queryBuilder->limit( $perPage );
		if ( $uuidArray = $paginationStatement->getUuidsFromPageNumber( $pageNumber ) ) {
			$modifiers = $this->getUuidModifiers( $queryBuilder, $uuidArray, function ( QueryBuilder $modifiedBuilder, array $reducedUuidArray ) {

				$modifiedBuilder->whereIn( 'uuid', $reducedUuidArray );


				return $modifiedBuilder;
			} );
			$paginationStatement->setResults( $queryBuilder->getNodeModifierStatement( $queryBuilder->columns, $modifiers ) );
		}
		if ( $queryBuilder->isUseCache() ) {
			$this->getPdoCache()->write( $paginationStatementHash, $paginationStatement );
		}

		return $paginationStatement;


	}

	/**
	 * @param string $table
	 *
	 * @return bool
	 */
	public function clearTableCache( string $table ): bool {
		return (bool) $this->getPdoCache()->cleanAllMatching( $table . ':' );
	}

	/**
	 * @param string $table
	 *
	 * @return bool
	 */
	public function clearTablePaginationCache( string $table ): bool {
		return (bool) $this->getPdoCache()->cleanAllMatching( $table . ':pag-' );
	}

	/**
	 * @param QueryBuilder $queryBuilder
	 * @param array $uuidArray
	 * @param \Closure $queryChanges
	 *
	 * @return NodeQueryModifiers
	 */
	private function getUuidModifiers( QueryBuilder $queryBuilder, array $uuidArray, \Closure $queryChanges ): NodeQueryModifiers {
		$nodeUuids = [];
		foreach ( $uuidArray as & $uuid ) {
			if ( ! $uuid instanceof Uuid ) {
				$uuid = new Uuid( $uuid );
			}
			if ( ! isset( $nodeUuids[ $uuid->getNode()->getName() ] ) ) {
				$nodeUuids[ $uuid->getNode()->getName() ] = [];
			}
			$nodeUuids[ $uuid->getNode()->getName() ][] = $uuid->toString();
		}

		$modifiers = [];

		foreach ( $nodeUuids as $nodeName => $reducedIds ) {
			$modifiedBuilder = clone( $queryBuilder );
			$modifiedBuilder = call_user_func_array( $queryChanges, [ $modifiedBuilder, $reducedIds ] );
			$modifiers[]     = new NodeQueryModifier( ShardMatrix::getConfig()->getNodes()->getNodeByName( $nodeName ), $modifiedBuilder );

		}

		return new NodeQueryModifiers( $modifiers );
	}

	/**
	 * @return PdoCacheInterface
	 */
	public function getPdoCache(): PdoCacheInterface {
		return ShardMatrix::getPdoCacheService();
	}

	/**
	 * @param NodeQueries $nodeQueries
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 * @param string|null $calledMethod
	 *
	 * @return NodeQueriesAsyncInterface
	 */
	protected function getNodeQueriesAsync( NodeQueries $nodeQueries, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null ): NodeQueriesAsyncInterface {

		$asyncClass = ShardMatrix::getNodeQueriesAsyncClass();

		return new $asyncClass( $this, $nodeQueries, $orderByColumn, $orderByDirection, $calledMethod );
	}

	public function __destruct() {
		$this->getPdoCache()->runCleanPolicy( $this );
	}


}