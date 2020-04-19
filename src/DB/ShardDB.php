<?php


namespace ShardMatrix\DB;


use Cassandra\Statement;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\Nodes;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Table;
use ShardMatrix\Uuid;

class ShardDB {
	/**
	 * @var string
	 */
	protected $defaultRowReturnClass = ResultRow::class;
	/**
	 * @var array
	 */
	protected $resultRowReturnClasses = [];
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
			->execute( $node, $sql, $bind, $uuid, __METHOD__ );

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
	 *
	 * @return ShardMatrixStatement|null
	 */
	public function uuidUpdate( Uuid $uuid, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		NodeDistributor::setFromUuid( $uuid );

		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( $uuid->getNode(), $sql, $bind, $uuid, __METHOD__ );

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
			->execute( $uuid->getNode(), $sql, $bind, $uuid, __METHOD__ );
	}

	/**
	 * @param Uuid $uuid
	 *
	 * @return ResultRow|null
	 */
	public function getByUuid( Uuid $uuid ): ?ResultRow {

		return $this->uuidQuery(
			$uuid,
			"select * from {$uuid->getTable()->getName()} where uuid = :uuid limit 1;"
		)->fetchResultRow();
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
		return $this->execute( $node, $sql, $bind, null, __METHOD__ );
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
	public function nodesQuery( Nodes $nodes, string $sql, ?array $bind = null, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null ) {
		$queryPidUuid = uniqid( getmypid() . '-' );
		foreach ( $nodes as $node ) {
			$pid = pcntl_fork();
			if ( $pid == - 1 ) {
				die( 'could not fork' );
			} else if ( $pid ) {
				// we are the parent
				pcntl_wait( $status ); //Protect against Zombie children
			} else {
				$stmt = $this->execute( $node, $sql, $bind, null, $calledMethod ?? __METHOD__ );
				if ( $stmt ) {
					$stmt->__preSerialize();
				}
				file_put_contents( ShardMatrix::getPdoCachePath() . '/' . $queryPidUuid . '-' . getmypid(), serialize( $stmt ) );
				exit;
			}
		}
		$results = [];
		foreach ( glob( ShardMatrix::getPdoCachePath() . '/' . $queryPidUuid . '-*' ) as $filename ) {
			$result = unserialize( file_get_contents( $filename ) );
			if ( $result ) {
				$results[] = $result;
			}
			unlink( $filename );
		}

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

		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $tableName );

		return $this->nodesQuery( $nodes, $sql, $bind, $orderByColumn, $orderByDirection, __METHOD__ );
	}


	/**
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 * @param Uuid|null $uuid
	 * @param string $calledMethod
	 *
	 * @return ShardMatrixStatement|null
	 */
	private function execute( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null, string $calledMethod ): ?ShardMatrixStatement {
		try {
			$db   = Connections::getNodeConnection( $node );
			$stmt = $db->prepare( $sql );
			$stmt->execute( $bind );
			if ( $stmt ) {
				if ( $uuid ) {
					$node->setLastUsedTableName( $uuid->getTable()->getName() );
				}
				$shardStmt = new ShardMatrixStatement( $stmt, $node, $uuid, $this->getRowReturnClassByNode( $node ) );
				if ( strpos( strtolower( trim( $sql ) ), 'insert ' ) === 0 ) {
					if ( $stmt->rowCount() > 0 && $uuid ) {
						$shardStmt->setLastInsertUuid( $uuid );
					}
				}
				$shardStmt->setSuccessChecked( $this->executeCheckSuccessFunction( $shardStmt, $calledMethod ) );

				return $shardStmt;
			}
		} catch ( \Exception | \TypeError | \Error $exception ) {
			/**
			 * TODO do something here if exception is thrown
			 */
			echo $exception->getMessage();
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
	 * @param ShardMatrixStatement $statements
	 * @param string $calledMethod
	 *
	 * @return bool|null
	 */
	private function executeCheckSuccessFunction( ShardMatrixStatement $statement, string $calledMethod ): ?bool {
//		$this->uniqueColumns( $statement, str_replace( static::class . '::', '', $calledMethod ) );
		if ( $this->checkSuccessFunction ) {
			return call_user_func_array( $this->checkSuccessFunction, [
				$statement,
				str_replace( static::class . '::', '', $calledMethod )
			] );
		}

		return null;
	}

	private function getRowReturnClassByNode( Node $node ): string {

		if ( isset( $this->getResultRowReturnClasses()[ $node->getLastUsedTableName() ] ) ) {
			return $this->getResultRowReturnClasses()[ $node->getLastUsedTableName() ];
		}

		return $this->getDefaultRowReturnClass();
	}

	/**
	 * @param string $defaultRowReturnClass
	 *
	 * @return ShardDB
	 */
	public function setDefaultRowReturnClass( string $defaultRowReturnClass ): ShardDB {
		$this->defaultRowReturnClass = $defaultRowReturnClass;

		return $this;
	}

	/**
	 * @return string
	 */
	private function getDefaultRowReturnClass(): string {
		return $this->defaultRowReturnClass;
	}

	private function uniqueColumns( ShardMatrixStatement $statement, string $calledMethod ) {
//			switch($calledMethod){
//				case 'insert':
//				case 'uuidUpdate':
//				$statement->getUuid()->getTable()->getUniqueColumns();
//
//				break;
//
//			}
//			$statement->getUuid()->getTable();
//
//			$email = $this->getByUuid( $statement->getUuid() )->email;
//			$checkDupes = $this->nodesQuery( $statement->getAllTableNodes(), "select uuid from users where email = :email and uuid != :uuid", [ ':email' => $email ,':uuid' => $statement->getUuid()->toString()] );
//			if($checkDupes->isSuccessful()){
//				$this->deleteByUuid( $statement->getUuid());
//				throw new Exception('Duplicate Record');
//			}

	}

	/**
	 * @param array $resultRowReturnClasses
	 *
	 * @return $this
	 */
	public function setResultRowReturnClasses( array $resultRowReturnClasses ): ShardDB {
		$this->resultRowReturnClasses = $resultRowReturnClasses;

		return $this;
	}

	/**
	 * @return array
	 */
	private function getResultRowReturnClasses(): array {
		return $this->resultRowReturnTypes;
	}

}