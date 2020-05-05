<?php


namespace ShardMatrix;


use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\DB\ShardMatrixStatement;
use ShardMatrix\DB\ShardMatrixStatements;
use ShardMatrix\GoThreaded\Client;

/**
 * Class NodeQueriesGoThreaded
 * @package ShardMatrix
 */
class NodeQueriesGoThreaded implements NodeQueriesAsyncInterface {

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
		$client = new Client();

		$results = $client->execQueries( $this->nodeQueries )->getResults();
		$client->close();
		$statementResult = [];
		if ( $resultNodes = $results->getNodes() ) {

			foreach ( $resultNodes as $nodeResult ) {
				foreach ( $this->nodeQueries->getNodeQueries() as $query ) {
					if ( $query->getNode()->getName() == $nodeResult->getNodeName() ) {
						$statementResult[] = ( new ShardMatrixStatement( null, $query->getNode(), null ) )->setDataFromGoThreadedResult( $nodeResult );
					}
				}

			}

		}

		return new ShardMatrixStatements( $statementResult, $this->orderByColumn, $this->orderByDirection );


	}
}