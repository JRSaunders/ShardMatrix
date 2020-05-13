<?php


namespace ShardMatrix;


use DevPledge\Integrations\Cache\CacheException;
use Predis\Client;
use Predis\Collection\Iterator\Keyspace;
use ShardMatrix\DB\ShardDB;

/**
 * Class PdoCacheRedis
 * @package ShardMatrix
 */
class PdoCacheRedis implements PdoCacheInterface {
	protected Client $redis;

	public function __construct( Client $redis ) {
		$this->redis = $redis;
	}

	public function read( string $key ) {
		$result = unserialize( $this->redis->get( $key ) );
		if ( $result && strlen( $result ) ) {
			return $result;
		}

		return null;
	}

	public function scan( string $key ): array {
		$key     = rtrim( $key, "*" );
		$results = [];
		foreach ( new Keyspace( $this->redis, $key . '*', 1000 ) as $matchKey ) {
			$results[] = $this->read( $matchKey );
		}

		return $results;
	}

	public function scanAndClean( string $key ): array {
		$key     = rtrim( $key, "*" );
		$matches = [];
		$results = [];
		foreach ( new Keyspace( $this->redis, $key . '*', 1000 ) as $matchKey ) {

			$matches[] = $matchKey;
			$results[] = $this->read( $matchKey );
		}

		$this->redis->del( $matches );

		return $results;
	}

	public function write( string $key, string $data ): bool {
		return (bool) $this->redis->setex( $key, 600, serialize( $data ) );
	}

	public function clean( string $key ): bool {
		return (bool) $this->redis->del( [ $key ] );
	}

	public function runCleanPolicy( ShardDB $shardDb ): void {
		//do nothing
	}
}