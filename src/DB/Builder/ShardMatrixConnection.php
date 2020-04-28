<?php


namespace ShardMatrix\Db\Builder;


use Doctrine\DBAL\Connection as DoctrineConnection;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use ShardMatrix\Config;
use ShardMatrix\DB\Connections;
use ShardMatrix\DB\Exception;
use ShardMatrix\DB\Interfaces\DBDataRowTransactionsInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\DB\Models\EloquentDataRowModel;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\Nodes;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Uuid;

/**
 * Class ShardMatrixConnection
 * @package ShardMatrix\Db\Illuminate
 */
class ShardMatrixConnection extends Connection {

	protected Node $node;

	protected ?Nodes $nodes = null;

	/**
	 * ShardMatrixConnection constructor.
	 *
	 * @param Node $node
	 * @param string $database
	 * @param string $tablePrefix
	 * @param array $config
	 */
	public function __construct( Node $node, $database = '', $tablePrefix = '', array $config = [] ) {
		$this->node = $node;
		parent::__construct( $this->getNodePdo(), $database, $tablePrefix, $config );
	}

	/**
	 * @return Node
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @return \PDO
	 */
	public function getNodePdo(): \PDO {
		return Connections::getNodeConnection( $this->getNode() );
	}

	/**
	 * @return QueryBuilder
	 * @throws Exception
	 */
	public function query(): QueryBuilder {
		return new QueryBuilder(
			$this, $this->getQueryGrammar(), $this->getPostProcessor()
		);
	}

	/**
	 * @param QueryBuilder $queryBuilder
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function prepareQuery( QueryBuilder $queryBuilder ): ShardMatrixConnection {

		$queryBuilder->connection = $this;
		$queryBuilder->grammar    = $this->getQueryGrammar();
		$queryBuilder->processor  = $this->getPostProcessor();

		return $this;
	}

	/**
	 * @return \Illuminate\Database\Query\Grammars\Grammar|MySqlGrammar
	 * @throws Exception
	 */
	public function getQueryGrammar() {
		$returnGrammar = $this->getDefaultQueryGrammar();
		switch ( $this->getNode()->getDsn()->getConnectionType() ) {
			case 'mysql':
				$returnGrammar = new MySqlGrammar();
				break;
			case 'pgsql':
				$returnGrammar = new PostgresGrammar();
				break;
			case 'sqlite':
				throw new Exception( 'SQL LITE IS NOT SUPPORTED' );
				break;
		}

		return $returnGrammar;
	}

	/**
	 * @return \Illuminate\Database\Schema\Grammars\Grammar|\Illuminate\Database\Schema\Grammars\MySqlGrammar|\Illuminate\Database\Schema\Grammars\PostgresGrammar
	 * @throws Exception
	 */
	public function getSchemaGrammar() {
		$returnGrammar = $this->getDefaultSchemaGrammar();
		switch ( $this->getNode()->getDsn()->getConnectionType() ) {
			case 'mysql':
				$returnGrammar = new \Illuminate\Database\Schema\Grammars\MySqlGrammar();
				break;
			case 'pgsql':
				$returnGrammar = new \Illuminate\Database\Schema\Grammars\PostgresGrammar();
				break;
			case 'sqlite':
				throw new Exception( 'SQL LITE IS NOT SUPPORTED' );
				break;
		}

		return $returnGrammar;
	}

	/**
	 * @return \Illuminate\Database\Query\Grammars\Grammar|MySqlGrammar
	 */
	public function getDefaultQueryGrammar() {
		return new MySqlGrammar();
	}

	/**
	 * @return MySqlProcessor|\Illuminate\Database\Query\Processors\Processor
	 * @throws Exception
	 */
	public function getPostProcessor() {
		$returnProcessor = $this->getDefaultPostProcessor();
		switch ( $this->getNode()->getDsn()->getConnectionType() ) {
			case 'mysql':
				$returnProcessor = new MySqlProcessor();
				break;
			case 'pgsql':
				$returnProcessor = new PostgresProcessor();
				break;
			case 'sqlite':
				throw new Exception( 'SQL LITE IS NOT SUPPORTED' );
				break;
		}

		return $returnProcessor;
	}

	/**
	 * @param $table
	 * @param null $as
	 *
	 * @return ShardMatrixConnection
	 * @throws \ShardMatrix\Exception
	 */
	public function shardTable( $table, $as = null ) {
		NodeDistributor::clearGroupNodes();

		return $this->table( $table, $as );
	}

	/**
	 * @return MySqlProcessor|\Illuminate\Database\Query\Processors\Processor
	 */
	public function getDefaultPostProcessor() {
		return new MySqlProcessor();
	}

	/**
	 * @param \Closure|\Illuminate\Database\Query\Builder|string $table
	 * @param null $as
	 *
	 * @return QueryBuilder
	 * @throws \ShardMatrix\Exception
	 */
	public function table( $table, $as = null ) {
		if ( $this instanceof UnassignedConnection ) {
			$this->node = NodeDistributor::getNode( $table );
			$this->pdo  = $this->getNodePdo();
		}
		if ( ! $this->getNode()->containsTableName( $table ) || ! isset( $this->pdo ) ) {
			$this->node = NodeDistributor::getNode( $table );
			$this->pdo  = $this->getNodePdo();
		}

		return parent::table( $table, $as );
	}

	/**
	 * @param $table
	 * @param null $as
	 *
	 * @return QueryBuilder
	 * @throws \ShardMatrix\Exception
	 */
	public function allNodesTable( $table, $as = null ): QueryBuilder {

		$this->nodes = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $table );

		return $this->table( $table, $as );
	}

	/**
	 * @return bool
	 */
	public function hasNodes(): bool {
		return isset( $this->nodes );
	}

	/**
	 * @return Nodes|null
	 */
	public function getNodesClear(): ?Nodes {
		$clonedNodes = null;
		if ( $this->nodes ) {
			$clonedNodes = clone( $this->nodes );
			$this->nodes = null;
		}

		return $clonedNodes;
	}

	/**
	 * @param $uuid
	 *
	 * @return DBDataRowTransactionsInterface|null
	 * @throws Exception
	 * @throws \ShardMatrix\Exception
	 */
	public function getByUuid( $uuid ): ?DBDataRowTransactionsInterface {
		if ( ! $uuid instanceof Uuid ) {
			$uuid = new Uuid( $uuid );
		}

		return $this->table( $uuid->getTable()->getName() )->whereUuid( $uuid )->first( [ '*' ] );
	}

	/**
	 * @return SchemaBuilder
	 */
	public function getSchemaBuilder() {
		if ( is_null( $this->schemaGrammar ) ) {
			$this->useDefaultSchemaGrammar();
		}

		return new SchemaBuilder( $this );
	}

	/**
	 * @return \Illuminate\Database\Schema\Grammars\Grammar|\Illuminate\Database\Schema\Grammars\MySqlGrammar
	 */
	public function getDefaultSchemaGrammar() {
		return new \Illuminate\Database\Schema\Grammars\MySqlGrammar();
	}

	/**
	 * @return Driver|\Doctrine\DBAL\Driver\PDOPgSql\Driver
	 * @throws Exception
	 */
	public function getDoctrineDriver(){

		$returnProcessor = $this->getDefaultDoctrineDriver();
		switch ( $this->getNode()->getDsn()->getConnectionType() ) {
			case 'mysql':
				$returnProcessor = new Driver();
				break;
			case 'pgsql':
				$returnProcessor = new \Doctrine\DBAL\Driver\PDOPgSql\Driver();
				break;
			case 'sqlite':
				throw new Exception( 'SQL LITE IS NOT SUPPORTED' );
				break;
		}

		return $returnProcessor;

	}

	public function getDefaultDoctrineDriver(){
		return new Driver();
	}

	public function getDoctrineConnection() {
		if (is_null($this->doctrineConnection)) {
			$driver = $this->getDoctrineDriver();

			$this->doctrineConnection = new DoctrineConnection(array_filter([
				'pdo' => $this->getPdo(),
				'dbname' => $this->getNode()->getDsn()->getDbname(),
				'driver' => $driver->getName(),
				'serverVersion' => $this->getConfig('server_version'),
			]), $driver);
		}

		return $this->doctrineConnection;
	}


}