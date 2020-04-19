<?php


namespace ShardMatrix\DB;


use Cassandra\Statement;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Table;
use ShardMatrix\Uuid;

class ShardQuery {

	protected ?\Closure $checkSuccessFunction = null;

	public function insert( string $tableName, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		$node = NodeDistributor::getNode( $tableName );
		if ( $node->isInsertData() ) {
			$uuid = Uuid::make( $node, new Table( $tableName ) );

			return $this
				->uuidBind( $uuid, $sql, $bind )
				->execute( $node, $sql, $bind, $uuid );
		}

		return null;
	}

	/**
	 * @param Uuid $uuid
	 * @param string $sql
	 * @param $bind
	 *
	 * @return ShardQuery
	 */
	private function uuidBind( Uuid $uuid, string $sql, &$bind ): ShardQuery {
		if ( strpos( $sql, ':uuid ' ) !== false ) {
			$bind[':uuid'] = $uuid->__toString();
		}

		return $this;
	}

	public function uuidUpdate( Uuid $uuid, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		NodeDistributor::setFromUuid( $uuid );

		return $this
			->uuidBind( $uuid, $sql, $bind )
			->execute( $uuid->getNode(), $sql, $bind, $uuid );

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
			->execute( $uuid->getNode(), $sql, $bind, $uuid );
	}

	/**
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 *
	 * @return ShardMatrixStatement|null
	 */
	public function nodeQuery( Node $node, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		return $this->execute( $node, $sql, $bind );
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
	public function allNodeQuery(
		string $tableName, string $sql, ?array $bind = null, ?string $orderByColumn = null, ?string $orderByDirection = null
	): ?ShardMatrixStatements {
		$queryPidUuid = uniqid( getmypid() . '-' );
		$nodes        = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $tableName );
		foreach ( $nodes as $node ) {
			$pid = pcntl_fork();
			if ( $pid == - 1 ) {
				die( 'could not fork' );
			} else if ( $pid ) {
				// we are the parent
				pcntl_wait( $status ); //Protect against Zombie children
			} else {
				$stmt = $this->execute( $node, $sql, $bind );
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
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 * @param Uuid|null $uuid
	 *
	 * @return \ShardMatrixStatement|null
	 */
	private function execute( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null ): ?ShardMatrixStatement {
		$stmt = Connections::getNodeConnection( $node )->prepare( $sql );
		$stmt->execute( $bind );
		if ( $stmt ) {
			$shardStmt = new ShardMatrixStatement( $stmt, $node, $uuid );
			$shardStmt->setSuccessChecked( $this->executeCheckSuccessFunction( $shardStmt ) );

			return $shardStmt;
		}

		return null;
	}

	/**
	 * @param \Closure|null $checkSuccessFunction
	 *
	 * @return $this|ShardMatrix
	 */
	public function setCheckSuccessFunction( ?\Closure $checkSuccessFunction ): ShardMatrix {
		$this->checkSuccessFunction = $checkSuccessFunction;

		return $this;
	}

	/**
	 * @param ShardMatrixStatement $statements
	 *
	 * @return bool|null
	 */
	private function executeCheckSuccessFunction( ShardMatrixStatement $statements ): ?bool {
		if ( $this->checkSuccessFunction ) {
			return call_user_func_array( $this->checkSuccessFunction, [ $statements ] );
		}

		return null;
	}

}