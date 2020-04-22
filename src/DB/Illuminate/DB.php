<?php


namespace ShardMatrix\Db\Illuminate;


/**
 * Class DB
 * @package ShardMatrix\Db\Illuminate
 * @method static QueryBuilder table(string $table, string $as = null)
 */
class DB extends \Illuminate\Support\Facades\DB {

	static public function __callStatic( $method, $args ) {
		$instance = new UnassignedConnection();

		if (! $instance) {
			throw new RuntimeException('A facade root has not been set.');
		}

		return $instance->$method(...$args);
	}
}