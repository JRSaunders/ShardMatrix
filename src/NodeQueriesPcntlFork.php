<?php


namespace ShardMatrix;


use ShardMatrix\DB\Connections;
use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\PreStatement;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\DB\ShardMatrixStatements;

/**
 * Class NodeQueriesPcntlFork
 * @package ShardMatrix
 */
class NodeQueriesPcntlFork implements NodeQueriesAsyncInterface {
	protected ShardDB $shardDb;
	protected NodeQueries $nodeQueries;
	protected ?string $orderByColumn = null;
	protected ?string $orderByDirection = null;
	protected ?string $calledMethod = null;

	/**
	 * NodeQueriesAsync constructor.
	 *
	 * @param ShardDB $shardDb
	 * @param NodeQueries $nodeQueries
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 * @param string|null $calledMethod
	 */
	public function __construct( ShardDB $shardDb, NodeQueries $nodeQueries, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null ) {
		$this->shardDb          = $shardDb;
		$this->nodeQueries      = $nodeQueries;
		$this->orderByColumn    = $orderByColumn;
		$this->orderByDirection = $orderByDirection;
		$this->calledMethod     = $calledMethod;

	}

	/**
	 * @return ShardMatrixStatements
	 * @throws DB\DuplicateException
	 * @throws DB\Exception
	 */
	public function getResults(): ShardMatrixStatements {
		$queryPidUuid = uniqid( getmypid() . '-' );
		$pids         = [];

		foreach ( $this->nodeQueries as $nodeQuery ) {
			$pid = pcntl_fork();
			Connections::closeConnections();
			if ( $pid == - 1 ) {
				die( 'could not fork' );
			} else if ( $pid ) {

				$pids[] = $pid;

			} else {
				$stmt = $this->shardDb->execute( new PreStatement( $nodeQuery->getNode(), $nodeQuery->getSql(), $nodeQuery->getBinds(), null, null, $this->calledMethod ) );
				if ( $stmt ) {
					$stmt->__preSerialize();
				}
				$this->shardDb->getPdoCache()->write( $queryPidUuid . '-' . getmypid(), $stmt );
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

		$results = $this->shardDb->getPdoCache()->scanAndClean( $queryPidUuid . '-' );

		if ( $results ) {
			return new ShardMatrixStatements( $results, $this->orderByColumn, $this->orderByDirection );
		}

		return new ShardMatrixStatements( [], $this->orderByColumn, $this->orderByDirection );
	}
}