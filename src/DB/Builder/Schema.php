<?php


namespace ShardMatrix\Db\Builder;

use ShardMatrix\DB\Connections;
use ShardMatrix\Node;
use ShardMatrix\ShardMatrix;

/**
 * Class Schema
 * @package ShardMatrix\Db\Builder
 */
class Schema extends \Illuminate\Support\Facades\Schema {
	/**
	 * @param string|null $name
	 *
	 * @return SchemaBuilder
	 */
	static public function connection( $name ) {
		return ( new ShardMatrixConnection( Connections::getNodeConnection( ShardMatrix::getConfig()->getNodes()->getNodeByName( $name ) ) ) )->getSchemaBuilder();
	}

	/**
	 * @return SchemaBuilder
	 */
	protected static function getFacadeAccessor() {
		return ( new ShardMatrixConnection( Connections::getLastUsedNode() ) )->getSchemaBuilder();
	}

	public static function __callStatic($method, $args)
	{
		$instance = new UnassignedConnection();

		return $instance->getSchemaBuilder()->$method( ...$args );
	}
}