<?php


namespace ShardMatrix;


use DevPledge\Integrations\Cache\CacheException;
use Predis\Client;
use Predis\Collection\Iterator\Keyspace;
use ShardMatrix\DB\Interfaces\PreSerialize;
use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\DB\ShardDB;

/**
 * Class PdoCacheRedis
 * @package ShardMatrix
 */
class PdoCacheRedis implements PdoCacheInterface {
	/**
	 * @var Client
	 */
	protected Client $redis;
	/**
	 * @var int
	 */
	protected int $cacheTime;

	/**
	 * PdoCacheRedis constructor.
	 *
	 * @param Client $redis
	 * @param int $cacheTime
	 */
	public function __construct( Client $redis, int $cacheTime = 600 ) {
		$this->cacheTime = $cacheTime;
		$this->redis     = $redis;
	}

	public function read( string $key ) {
		$raw = $this->redis->get( $key );
		if ( strlen( $raw ) ) {
			$data = unserialize( gzinflate( $raw ) );
			if ( $data instanceof ResultsInterface ) {
				$data->setFromCache( true );
			}

			return $data;
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

	public function write( string $key, $data ): bool {
		if ( $data instanceof PreSerialize ) {
			$data->__preSerialize();
		}

		return (bool) $this->redis->setex( $key, $this->cacheTime, gzdeflate( serialize( $data ) ) );
	}

	public function clean( string $key ): bool {
		return (bool) $this->redis->del( [ $key ] );
	}

	public function runCleanPolicy( ShardDB $shardDb ): void {
		//do nothing
	}
}