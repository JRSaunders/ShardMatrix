<?php


namespace ShardMatrix\Db\Builder;

use ShardMatrix\DB\Connections;
use ShardMatrix\Node;
use ShardMatrix\Nodes;
use ShardMatrix\ShardMatrix;

/**
 * Class Schema
 * @package ShardMatrix\Db\Builder
 * @method static SchemaBuilder silent()
 * @method static SchemaBuilder node( Node $node )
 * @method static SchemaBuilder nodes( Nodes $node )
 */
class Schema extends \Illuminate\Support\Facades\Schema {
	/**
	 * node name
	 *
	 * @param string|null $name
	 *
	 * @return SchemaBuilder
	 */
	static public function connection( $name ) {
		$node = ShardMatrix::getConfig()->getNodes()->getNodeByName( $name );

		return ( new ShardMatrixConnection( $node ) )->getSchemaBuilder()->node( $node );
	}

	/**
	 * @return SchemaBuilder
	 */
	protected static function getFacadeAccessor() {
		return ( new ShardMatrixConnection( Connections::getLastUsedNode() ) )->getSchemaBuilder();
	}

	/**
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function __callStatic( $method, $args ) {
		$instance = new UnassignedConnection();

		return $instance->getSchemaBuilder()->$method( ...$args );
	}
}