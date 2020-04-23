<?php

namespace ShardMatrix\Db\Illuminate;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ShardMatrix\DB\Exception;
use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\NodeQuery;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\DB\ShardMatrixStatement;
use ShardMatrix\DB\ShardMatrixStatements;
use ShardMatrix\NodeDistributor;
use ShardMatrix\Table;
use ShardMatrix\Uuid;

/**
 * Class QueryBuilder
 * @package ShardMatrix\Db\Illuminate
 */
class QueryBuilder extends \Illuminate\Database\Query\Builder {

	protected ? Uuid $uuid = null;

	/**
	 * QueryBuilder constructor.
	 *
	 * @param ConnectionInterface|null $connection
	 * @param Grammar|null $grammar
	 * @param Processor|null $processor
	 */
	public function __construct( ?ConnectionInterface $connection = null, ?Grammar $grammar = null, ?Processor $processor = null ) {
		parent::__construct( $connection ?? new UnassignedConnection(), $grammar, $processor );
	}

	public function getConnection(): ShardMatrixConnection {
		return $this->connection;
	}

	/**
	 * @param ShardMatrixConnection $connection
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function setShardMatrixConnection( ShardMatrixConnection $connection ): QueryBuilder {
		if ( $connection instanceof UnassignedConnection ) {
			throw new Exception( 'Connection has to be assigned' );
		}
		$connection->prepareQuery( $this );

		return $this;
	}


	protected function getPrimaryOrderColumn(): ?string {
		if ( $this->orders ) {
			$order = $this->orders[0];

			return $order['column'] ?? null;
		}

		return null;
	}

	protected function getPrimaryOrderDirection(): ?string {
		if ( $this->orders ) {
			$order = $this->orders[0];

			return $order['direction'] ?? null;
		}

		return null;
	}


	/**
	 * @param Uuid| string $uuid
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function uuidAsNodeReference( $uuid ): QueryBuilder {
		if ( is_string( $uuid ) ) {
			$uuid = new Uuid( $uuid );
		}
		if ( ! $uuid instanceof Uuid ) {
			throw new Exception( 'Uuid Object Required' );
		}
		$this->uuid = $uuid;
		$this->setShardMatrixConnection( new ShardMatrixConnection( $uuid->getNode() ) );

		return $this;
	}

	/**
	 * @param Uuid | string $uuid
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function whereUuid( $uuid ): QueryBuilder {
		if ( is_string( $uuid ) ) {
			$uuid = new Uuid( $uuid );
		}
		if ( ! $uuid instanceof Uuid ) {
			throw new Exception( 'Uuid Object Required' );
		}
		$this->setShardMatrixConnection( new ShardMatrixConnection( $uuid->getNode() ) );
		parent::where( 'uuid', '=', $uuid->toString() );
		$this->uuid = $uuid;

		return $this;
	}

	public function where( $column, $operator = null, $value = null, $boolean = 'and' ) {
		if ( $column == 'uuid' && $operator == '=' ) {
			$this->uuid = new Uuid( $value );
			$this->setShardMatrixConnection( new ShardMatrixConnection( $this->uuid->getNode() ) );
		}

		return parent::where( $column, $operator, $value, $boolean );
	}

	public function newNode(): QueryBuilder {
		NodeDistributor::clearGroupNodes();

		return $this;
	}

	/**
	 * @param array $values
	 *
	 * @return Uuid|null
	 * @throws \ShardMatrix\Exception
	 */
	public function insert( array $values ): ?Uuid {
		$uuid   = Uuid::make( $this->getConnection()->getNode(), new Table( $this->from ) );
		$values = array_merge( [ 'uuid' => $uuid->toString() ], $values );
		if ( empty( $values ) ) {
			return null;
		}
		if ( ! is_array( reset( $values ) ) ) {
			$values = [ $values ];
		} else {
			foreach ( $values as $key => $value ) {
				ksort( $value );

				$values[ $key ] = $value;
			}
		}

		$result = ( new ShardDB() )->uuidInsert( $uuid, $this->grammar->compileInsert( $this, $values ), $this->cleanBindings( Arr::flatten( $values, 1 ) ) );
		if ( $result ) {
			return $result->getLastInsertUuid();
		}

		return null;
	}


	public function update( array $values ) {

		if ( $this->uuid ) {
			return ( new ShardDB() )->uuidUpdate( $this->uuid, $this->grammar->compileUpdate( $this, $values ),
				$this->cleanBindings(
					$this->grammar->prepareBindingsForUpdate( $this->bindings, $values )
				)
			);
		}

		return parent::update( $values );

	}

	/**
	 * @return ShardMatrixStatements|null
	 * @throws Exception
	 * @throws \ShardMatrix\DB\DuplicateException
	 */
	protected function returnNodesResults(): ?ShardMatrixStatements {
		if ( $nodes = $this->getConnection()->getNodesClear() ) {
			$nodeQueries = [];
			foreach ( $nodes as $node ) {
				$queryBuilder = clone( $this );
				( new ShardMatrixConnection( $node ) )->prepareQuery( $queryBuilder );
				$nodeQueries[] = new NodeQuery( $node, $queryBuilder->toSql(), $queryBuilder->getBindings() );
			}

			return ( new ShardDB() )->setDefaultRowReturnClass( Model::class )->nodeQueries( new NodeQueries( $nodeQueries ), $this->getPrimaryOrderColumn(), $this->getPrimaryOrderDirection(), __METHOD__ );
		}
	}

	/**
	 * @return ShardMatrixStatement|null
	 */
	protected function returnNodeResult(): ?ShardMatrixStatement {
		return ( new ShardDB() )->setDefaultRowReturnClass( Model::class )->nodeQuery( $this->getConnection()->getNode(), $this->toSql(), $this->getBindings() );
	}

	/**
	 * @param bool $asShardMatrixStatement
	 *
	 * @return Collection | ShardMatrixStatements | ShardMatrixStatement | null
	 */
	protected function returnResults( bool $asShardMatrixStatement = false ) {
		if ( $this->getConnection()->hasNodes() ) {
			$result = $this->returnNodesResults();
		} else {
			$result = $this->returnNodeResult();
		}
		if ( ! $asShardMatrixStatement ) {
			if ( $result ) {
				return new Collection( $result->fetchDataRows()->getDataRows() );
			}


			return new Collection( [] );
		}

		return $result;
	}

	public function delete( $id = null ) {
		$uuid = new Uuid( $id );
		if ( ! is_null( $uuid ) ) {
			$this->uuidAsNodeReference( $uuid );
			$this->where( $this->from . '.uuid', '=', $uuid->toString() );
		}

		return $this->connection->delete(
			$this->grammar->compileDelete( $this ), $this->cleanBindings(
			$this->grammar->prepareBindingsForDelete( $this->bindings )
		)
		);
	}

	/**
	 * @param string[] $columns
	 *
	 * @return Collection
	 */
	public function get( $columns = [ '*' ] ) {
		$this->select( $columns );

		return $this->returnResults();
	}

	/**
	 * @param int|string $uuid
	 * @param string[] $columns
	 *
	 * @return \Illuminate\Database\Eloquent\Model|mixed|object|QueryBuilder|null
	 * @throws Exception
	 */
	public function find( $uuid, $columns = [ '*' ] ) {
		if ( ! $uuid instanceof Uuid ) {
			$uuid = new Uuid( $uuid );
		}

		return $this->whereUuid( $uuid )->first( $columns );
	}


}