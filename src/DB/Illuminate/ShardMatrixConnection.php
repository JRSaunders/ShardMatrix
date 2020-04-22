<?php


namespace ShardMatrix\Db\Illuminate;


use Illuminate\Database\Connection;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Processors\MySqlProcessor;
use Illuminate\Database\Query\Processors\PostgresProcessor;
use ShardMatrix\DB\Connections;
use ShardMatrix\DB\Exception;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;

/**
 * Class ShardMatrixConnection
 * @package ShardMatrix\Db\Illuminate
 */
class ShardMatrixConnection extends Connection {

	protected Node $node;

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
	 * @return MySqlProcessor|\Illuminate\Database\Query\Processors\Processor
	 */
	public function getDefaultPostProcessor() {
		return new MySqlProcessor();
	}

	/**
	 * @param \Closure|\Illuminate\Database\Query\Builder|string $table
	 * @param null $as
	 *
	 * @return \Illuminate\Database\Query\Builder
	 * @throws \ShardMatrix\Exception
	 */
	public function table( $table, $as = null ) {
		if ( ! $this->getNode()->containsTableName( $table ) || ! isset( $this->pdo ) ) {
			$this->node = NodeDistributor::getNode( $table );
			$this->pdo  = $this->getNodePdo();
		}

		return parent::table( $table, $as );
	}



}