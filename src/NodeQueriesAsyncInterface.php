<?php


namespace ShardMatrix;


use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\DB\ShardMatrixStatements;

interface NodeQueriesAsyncInterface {
	/**
	 * NodeQueriesAsyncInterface constructor.
	 *
	 * @param ShardDB $shardDb
	 * @param NodeQueries $nodeQueries
	 * @param string|null $orderByColumn
	 * @param string|null $orderByDirection
	 * @param string|null $calledMethod
	 */
	public function __construct( ShardDB $shardDb, NodeQueries $nodeQueries, ?string $orderByColumn = null, ?string $orderByDirection = null, ?string $calledMethod = null );

	/**
	 * @return ShardMatrixStatements
	 */
	public function getResults(): ShardMatrixStatements;
}