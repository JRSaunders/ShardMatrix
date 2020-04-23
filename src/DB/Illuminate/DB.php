<?php


namespace ShardMatrix\Db\Illuminate;


use ShardMatrix\Uuid;

/**
 * Class DB
 * @package ShardMatrix\Db\Illuminate
 * @method static QueryBuilder table( string $table, string $as = null )
 * @method static Model getByUuid( $uuid )
 */
class DB extends \Illuminate\Support\Facades\DB {

	static public function __callStatic( $method, $args ) {
		$instance = new UnassignedConnection();

		if ( ! $instance ) {
			throw new RuntimeException( 'A facade root has not been set.' );
		}

		return $instance->$method( ...$args );
	}

}