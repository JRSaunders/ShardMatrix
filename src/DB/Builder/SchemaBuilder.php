<?php


namespace ShardMatrix\Db\Builder;


use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use ShardMatrix\DB\Exception;
use ShardMatrix\Nodes;
use ShardMatrix\ShardMatrix;

/**
 * Class SchemaBuilder
 * @package ShardMatrix\Db\Builder
 */
class SchemaBuilder extends Builder {
	/**
	 * @var Nodes|null
	 */
	protected ?Nodes $nodes = null;
	/**
	 * @var bool
	 */
	protected $throwExceptions = true;

	/**
	 * SchemaBuilder constructor.
	 *
	 * @param Connection|null $connection
	 */
	public function __construct( ?Connection $connection = null ) {
		parent::__construct( $connection ?? new UnassignedConnection() );
	}

	/**
	 * @param Nodes $nodes
	 *
	 * @return $this
	 */
	public function nodes( Nodes $nodes ): SchemaBuilder {
		$this->nodes = $nodes;

		return $this;
	}

	/**
	 * @param Node $node
	 *
	 * @return $this
	 */
	public function node( Node $node ): SchemaBuilder {
		$this->nodes = new Nodes( [ $node ] );

		return $this;
	}

	/**
	 * @return $this
	 */
	public function silent(): SchemaBuilder {
		$this->throwExceptions = false;

		return $this;
	}

	protected function getNodes( $table ) {
		if ( $this->nodes ) {
			return $this->nodes->getNodesWithTableName( $table );
		}

		return ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );
	}

	/**
	 * @param string $table
	 * @param Closure $callback
	 *
	 * @throws BuilderException
	 */
	public function create( $table, Closure $callback ) {
		$nodes = $this->getNodes( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::create( $table, $callback );
			} catch ( \Exception $exception ) {
				if ( $this->throwExceptions ) {
					throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
				}
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @throws BuilderException
	 */
	public function drop( $table ) {
		$nodes = $this->getNodes( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::drop( $table );
			} catch ( \Exception $exception ) {
				if ( $this->throwExceptions ) {
					throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
				}
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @throws BuilderException
	 */
	public function dropIfExists( $table ) {
		$nodes = $this->getNodes( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::dropIfExists( $table );
			} catch ( \Exception $exception ) {
				if ( $this->throwExceptions ) {
					throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
				}
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
		$nodes = $this->getNodes( $from );
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
					if ( $this->throwExceptions ) {
						throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
					}
				}
			} else {
				if ( $this->throwExceptions ) {
					throw new BuilderException( $node, 'Renamed Table is required in same shard matrix config table group for this node ' . $node->getName() );
				}
			}
		}

	}

	/**
	 * @param string $table
	 * @param Closure $callback
	 *
	 * @throws BuilderException
	 */
	public function table( $table, Closure $callback ) {
		$nodes = $this->getNodes( $table );
		if ( $nodes->countNodes() == 0 ) {
			throw new BuilderException( null, 'Table not specified in Shard Matrix Config' );
		}
		foreach ( $nodes as $node ) {
			try {
				$this->connection = new ShardMatrixConnection( $node );
				$this->grammar    = $this->connection->getSchemaGrammar();
				parent::table( $table, $callback );
			} catch ( \Exception $exception ) {
				if ( $this->throwExceptions ) {
					throw new BuilderException( $node, $exception->getMessage(), $exception->getCode(), $exception->getPrevious() );
				}
			}
		}
	}


}