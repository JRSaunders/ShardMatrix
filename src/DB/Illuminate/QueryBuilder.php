<?php

namespace ShardMatrix\Db\Illuminate;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Collection;
use ShardMatrix\DB\Exception;
use ShardMatrix\DB\ShardDB;
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


	/**
	 * @param $columns
	 *
	 * @return Collection|null
	 * @throws Exception
	 */
	public function getAllNodes( $columns ): ?Collection {
		if ( ! $this->columns ) {
			throw new Exception( 'Select Columns need to be set' );
		}
		if ( $this->orders ) {
			$order     = $this->orders[0];
			$column    = $order['column'];
			$direction = $order['direction'];
		}
		$result = ( new ShardDB() )->allNodesQuery( $this->from, $this->toSql(), $this->getBindings(), $column, $direction );
		if ( ! $result ) {
			return $result;
		}

		return new Collection( $result->fetchResultSet()->getResultSet() );
	}


	/**
	 * @param Uuid $uuid
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function uuid( Uuid $uuid ): QueryBuilder {
		$this->uuid = $uuid;
		$this->setShardMatrixConnection( new ShardMatrixConnection( $uuid->getNode() ) );

		return $this;
	}


	/**
	 * @param string $uuid
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function uuidString( string $uuid ): QueryBuilder {
		return $this->uuid( new Uuid( $uuid ) );
	}

	/**
	 * @param Uuid $uuid
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function useNodeFromUuid( Uuid $uuid ): QueryBuilder {
		$this->setShardMatrixConnection( new ShardMatrixConnection( $uuid->getNode() ) );

		return $this;
	}

	/**
	 * @param string $uuid
	 *
	 * @return QueryBuilder
	 * @throws Exception
	 */
	public function useNodeFromUuidString( string $uuid ): QueryBuilder {

		return $this->useNodeFromUuid( new Uuid( $uuid ) );

	}

	/**
	 * @param array $values
	 *
	 * @return bool
	 * @throws \ShardMatrix\Exception
	 */
	public function insert( array $values ) {
		$values = array_merge( [ 'uuid' => Uuid::make( $this->getConnection()->getNode(), $this->from )->toString() ], $values );

		return parent::insert( $values );
	}

//	public function get(){
//
//	}


}