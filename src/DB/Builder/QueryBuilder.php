<?php

namespace ShardMatrix\Db\Builder;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ShardMatrix\DB\DataRow;
use ShardMatrix\DB\Exception;
use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\DB\Models\EloquentDataRowModel;
use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\NodeQuery;
use ShardMatrix\DB\NodeQueryModifiers;
use ShardMatrix\DB\PaginationStatement;
use ShardMatrix\DB\PreStatement;
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


	protected ?string $primaryOrderDirection = null;

	protected ?string $primaryOrderColumn = null;

	/**
	 * @var Uuid|null
	 */
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

	/**
	 * @return ShardMatrixConnection
	 */
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
			throw new BuilderException( null, 'Connection has to be assigned' );
		}
		$connection->prepareQuery( $this );

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getPrimaryOrderColumn(): ?string {
		if ( isset( $this->primaryOrderColumn ) ) {
			return $this->primaryOrderColumn;
		}
		if ( $this->orders ) {
			$order = $this->orders[0];

			return $order['column'] ?? null;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getPrimaryOrderDirection(): ?string {
		if ( isset( $this->primaryOrderDirection ) ) {
			return $this->primaryOrderDirection;
		}
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
			throw new BuilderException( null, 'Uuid Object Required' );
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
			throw new BuilderException( null, 'Uuid Object Required' );
		}
		$this->setShardMatrixConnection( new ShardMatrixConnection( $uuid->getNode() ) );
		parent::where( 'uuid', '=', $uuid->toString() );
		$this->uuid = $uuid;

		return $this;
	}

	/**
	 * @param array|\Closure|string $column
	 * @param null $operator
	 * @param null $value
	 * @param string $boolean
	 *
	 * @return QueryBuilder
	 * @throws Exceptio
	 */
	public function where( $column, $operator = null, $value = null, $boolean = 'and' ) {
		if ( $column == 'uuid' && $operator == '=' ) {
			$this->uuid = new Uuid( $value );
			$this->setShardMatrixConnection( new ShardMatrixConnection( $this->uuid->getNode() ) );
		}

		return parent::where( $column, $operator, $value, $boolean );
	}

	/**
	 * @return string
	 */
	public function toSqlHash(): string {
		return md5( str_replace( [
			'"',
			'`',
			"'",
			"$",
			"?"
		], '-', strtolower( $this->toSql() . ( join( '-', $this->getBindings() ) ) ) ) );
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

	/**
	 * @param array $values
	 *
	 * @return int|ShardMatrixStatement|ShardMatrixStatements|null
	 * @throws Exception
	 * @throws \ShardMatrix\DB\DuplicateException
	 */
	public function update( array $values ) {

		if ( $this->uuid ) {
			return ( new ShardDB() )->uuidUpdate(
				$this->uuid,
				$this->grammar->compileUpdate( $this, $values ),
				$this->cleanBindings(
					$this->grammar->prepareBindingsForUpdate( $this->bindings, $values )
				)
			);
		}


		if ( $this->getConnection()->hasNodes() && ( $nodes = $this->getConnection()->getNodes() ) ) {
			$nodeQueries = [];
			foreach ( $nodes as $node ) {
				$queryBuilder = clone( $this );
				( new ShardMatrixConnection( $node ) )->prepareQuery( $queryBuilder );

				$nodeQueries[] = new NodeQuery( $node, $queryBuilder->getGrammar()->compileUpdate( $queryBuilder, $values ), $this->cleanBindings(
					$queryBuilder->getGrammar()->prepareBindingsForUpdate( $this->bindings, $values )
				) );
			}

			return ( new ShardDB() )->nodeQueries( new NodeQueries( $nodeQueries ), null, null, 'update' );
		}

		return parent::update( $values );

	}

	/**
	 * @param ShardDataRowInterface $dataRow
	 *
	 * @return bool|null
	 * @throws Exception
	 * @throws \ShardMatrix\DB\DuplicateException
	 */
	public function updateByDataRow( ShardDataRowInterface $dataRow ): ?bool {

		if ( ! ( $uuid = $dataRow->getUuid() ) ) {
			return null;
		}
		$values = $dataRow->__toArray();
		unset( $values['uuid'] );
		if ( isset( $values['modified'] ) ) {
			$array['modified'] = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
		}
		$this->from( $dataRow->getUuid()->getTable()->getName() )->whereUuid( $dataRow->getUuid() );

		$update = ( new ShardDB() )->execute( new PreStatement(
			$dataRow->getUuid()->getNode(), $this->grammar->compileUpdate( $this, $values ),
			$this->cleanBindings(
				$this->grammar->prepareBindingsForUpdate( $this->bindings, $values )
			),
			$dataRow->getUuid(),
			$dataRow,
			'update'
		) );
		if ( $update && $update->isSuccessful() ) {
			return true;
		}

		return false;
	}


	/**
	 * @param NodeQueryModifiers|null $modifiers
	 *
	 * @return ShardMatrixStatements|null
	 * @throws Exception
	 * @throws \ShardMatrix\DB\DuplicateException
	 * @throws \ShardMatrix\Exception
	 */
	protected function returnNodesResults( ?NodeQueryModifiers $modifiers = null ): ?ShardMatrixStatements {
		if ( $nodes = $this->getConnection()->getNodes() ) {
			$nodeQueries = [];
			foreach ( $nodes as $node ) {
				$queryBuilder = clone( $this );
				if ( $modifiers ) {
					$queryBuilder = $modifiers->modifyQueryForNode( $node, $queryBuilder );
					if ( ! $queryBuilder ) {
						continue;
					}
				}
				( new ShardMatrixConnection( $node ) )->prepareQuery( $queryBuilder );
				$nodeQueries[] = new NodeQuery( $node, $queryBuilder->toSql(), $queryBuilder->getBindings() );
			}

			return ( new ShardDB() )->setDefaultDataRowClass( EloquentDataRowModel::class )->nodeQueries( new NodeQueries( $nodeQueries ), $this->getPrimaryOrderColumn(), $this->getPrimaryOrderDirection(), __METHOD__ );
		}
	}

	/**
	 * @return ShardMatrixStatement|null
	 */
	protected function returnNodeResult(): ?ShardMatrixStatement {
		return ( new ShardDB() )->setDefaultDataRowClass( EloquentDataRowModel::class )->nodeQuery( $this->getConnection()->getNode(), $this->toSql(), $this->getBindings() );
	}


	/**
	 * @param bool $asShardMatrixStatement
	 *
	 * @return Collection|ShardMatrixStatement|ShardMatrixStatements|null
	 * @throws Exception
	 * @throws \ShardMatrix\DB\DuplicateException
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

	/**
	 * @param null $id
	 *
	 * @return int
	 * @throws Exception
	 */
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
	 * @param Uuid|null $uuid
	 * @param int $perPage
	 *
	 * @return QueryBuilder
	 */
	public function uuidMarkerPageAbove( $uuid = null, int $perPage = 15 ): QueryBuilder {
		if ( ! is_null( $uuid ) ) {
			$uuid = new Uuid( $uuid );
		}
		$uuid ?? $this->uuid;
		if ( $uuid ) {
			return $this->where( 'uuid', '>', $uuid->toString() )->orderBy( 'uuid' )->limit( $perPage );
		}

		return $this->limit( $perPage );
	}

	/**
	 * @param Uuid|null $uuid
	 * @param int $perPage
	 *
	 * @return QueryBuilder
	 */
	public function uuidMarkerPageBelow( ?Uuid $uuid = null, int $perPage = 15 ): QueryBuilder {
		if ( ! is_null( $uuid ) ) {
			$uuid = new Uuid( $uuid );
		}
		$uuid ?? $this->uuid;

		if ( $uuid ) {
			return $this->where( 'uuid', '<', $uuid->toString() )->orderBy( 'uuid' )->limit( $perPage );
		}

		return $this->limit( $perPage );
	}

	/**
	 * @param string[] $columns
	 *
	 * @return array|ResultsInterface
	 */
	protected function runPaginationCountQuery( $columns = [ '*' ] ) {
		$without = $this->unions ? [ 'orders', 'limit', 'offset' ] : [ 'columns', 'orders', 'limit', 'offset' ];

		return $this->cloneWithout( $without )
		            ->cloneWithoutBindings( $this->unions ? [ 'order' ] : [ 'select', 'order' ] )
		            ->setAggregate( 'count', $this->withoutSelectAliases( $columns ) )
		            ->getStatement( $columns );
	}

	/**
	 * @param string[] $columns
	 *
	 * @return int
	 */
	public function getCountForPagination( $columns = [ '*' ] ) {
		$results = $this->runPaginationCountQuery( $columns );
		// Once we have run the pagination count query, we will get the resulting count and
		// take into account what type of query it was. When there is a group by we will
		// just return the count of the entire results set since that will be correct.
		if ( isset( $this->groups ) ) {
			return count( $results->fetchAllArrays() );
		} elseif ( ! $results->isSuccessful() ) {
			return 0;
		} elseif ( $results->fetchDataRow() ) {
			return (int) $results->sumColumn( 'aggregate' );
		}

		return (int) array_change_key_case( (array) $results[0] )['aggregate'];
	}

	/**
	 * @param array|string[] $columns
	 *
	 * @return ResultsInterface
	 */
	public function getStatement( array $columns = [ '*' ] ): ResultsInterface {
		$this->select( $columns );

		return $this->returnResults( true );
	}

	public function getNodeModifierStatement( array $columns = [ '*' ], NodeQueryModifiers $modifiers ): ResultsInterface {
		return $this->returnNodesResults( $modifiers );
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

	/**
	 * @param string $function
	 * @param string[] $columns
	 *
	 * @return mixed
	 */
	public function aggregate( $function, $columns = [ '*' ] ) {
		$results      = $this->cloneWithout( $this->unions ? [] : [ 'columns' ] )
		                     ->cloneWithoutBindings( $this->unions ? [] : [ 'select' ] )
		                     ->setAggregate( $function, $columns )
		                     ->getStatement( $columns );
		$aggregateKey = 'aggregate';
		if ( $results->isSuccessful() ) {
			switch ( $function ) {
				case 'sum':
				case 'count':
					return $results->sumColumn( $aggregateKey );
					break;
				case 'avg':
					return $results->avgColumn( $aggregateKey );
					break;
				case 'min':
					return $results->minColumn( $aggregateKey );
					break;
				case 'max':
					return $results->maxColumn( $aggregateKey );
					break;
			}
		}
	}

	/**
	 * @param array|string[] $columns
	 * @param int $pageNumber
	 * @param int $perPage
	 * @param int|null $limitPages
	 */
	public function getPagination( array $columns = [ "*" ], int $pageNumber = 1, int $perPage = 15, ?int $limitPages = 10 ): PaginationStatement {
		$this->select( $columns );
		$shard = new ShardDB();

		return $shard->paginationByQueryBuilder( $this, $pageNumber, $perPage, $limitPages );
	}

	/**
	 * @param string|null $primaryOrderDirection
	 *
	 * @return $this
	 */
	public function setPrimaryOrderDirection( ?string $primaryOrderDirection ): QueryBuilder {
		$this->primaryOrderDirection = $primaryOrderDirection;

		return $this;
	}

	/**
	 * @param string|null $primaryOrderColumn
	 *
	 * @return QueryBuilder
	 */
	public function setPrimaryOrderColumn( ?string $primaryOrderColumn ): QueryBuilder {
		$this->primaryOrderColumn = $primaryOrderColumn;

		return $this;
	}


}