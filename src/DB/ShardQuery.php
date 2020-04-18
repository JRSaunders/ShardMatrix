<?php


namespace ShardMatrix\DB;


use ShardMatrix\NodeDistributor;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Table;
use ShardMatrix\Uuid;

class ShardQuery {


	public function insert( string $tableName, string $sql, ?array $bind = null ): ?ShardMatrixStatement {
		$node = NodeDistributor::getNode( $tableName );
		if ( $node->isInsertData() ) {
			$uuid = Uuid::make( $node, new Table( $tableName ) );
			$this->uuidBind( $uuid, $sql, $bind );

			return $this->execute( $node, $sql, $bind, $uuid );
		}

		return null;
	}

	private function uuidBind( Uuid $uuid, string $sql, &$bind ) {
		if ( strpos( $sql, ':uuid' ) ) {
			$bind[':uuid'] = $uuid->__toString();
		}
	}

	public function uuidUpdate( Uuid $uuid, string $sql, ?array $bind = null ): ?\ShardMatrixStatement {
		NodeDistributor::setFromUuid( $uuid );
		$this->uuidBind( $uuid, $sql, $bind );

		return $this->execute( $uuid->getNode(), $sql, $bind, $uuid );

	}

	public function uuidQuery( Uuid $uuid, string $sql, ?array $bind = null ) {
		$bind[':uuid'] = $uuid->__toString();

		return $this->execute( $uuid->getNode(), $sql, $bind, $uuid );
	}

	public function nodeQuery( Node $node, string $sql, ?array $bind = null ): ?\ShardMatrixStatement {
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
	public function allNodeQuery( string $tableName, string $sql, ?array $bind = null, ?string $orderByColumn = null, ?string $orderByDirection = null ): ?ShardMatrixStatements {
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
				file_put_contents( ShardMatrix::getPdoCachePath() . '/' . $queryPidUuid . '-' . getmypid(), serialize( $this->execute( $node, $sql, $bind ) ) );
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
	private function execute( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null ): ?\ShardMatrixStatement {
		$stmt = Connections::getNodeConnection( $node )->prepare( $sql );
		$stmt->execute( $bind );
		if ( $stmt ) {
			return new ShardMatrixStatement( $stmt, $node, $uuid );
		}

		return null;
	}

}