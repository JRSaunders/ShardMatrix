<?php


namespace ShardMatrix\Db\Builder;


use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use ShardMatrix\DB\Exception;
use ShardMatrix\ShardMatrix;

/**
 * Class SchemaBuilder
 * @package ShardMatrix\Db\Builder
 */
class SchemaBuilder extends Builder {
	/**
	 * SchemaBuilder constructor.
	 *
	 * @param Connection|null $connection
	 */
	public function __construct( ?Connection $connection = null ) {
		parent::__construct( $connection ?? new UnassignedConnection() );
	}


	/**
	 * @param string $table
	 * @param Closure $callback
	 *
	 * @throws BuilderException
	 */
	public function create( $table, Closure $callback ) {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::create( $table, $callback );
			} catch ( \Exception $exception ) {
				throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @throws BuilderException
	 */
	public function drop( $table ) {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::drop( $table );
			} catch ( \Exception $exception ) {
				throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @throws BuilderException
	 */
	public function dropIfExists( $table ) {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null,'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::dropIfExists( $table );
			} catch ( \Exception $exception ) {
				throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
			}
		}

	}

	/**
	 * @param string $from
	 * @param string $to
	 *
	 * @throws BuilderException
	 */
	public function rename( $from, $to ) {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $from );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {

			if ( $node->containsTableName( $to ) ) {
				try {
					$this->connection = new ShardMatrixConnection( $node );
					$this->grammar    = $this->connection->getSchemaGrammar();
					parent::rename( $from, $to );
				} catch ( \Exception $exception ) {
					throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
				}
			} else {
				throw new BuilderException( $node, 'Renamed Table is required in same shard matrix config table group for this node ' . $node->getName() );
			}
		}

	}

	public function table( $table, Closure $callback ) {
		$nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null,'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::table( $table, $callback );
			} catch ( \Exception $exception ) {
				throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
			}
		}
	}


}