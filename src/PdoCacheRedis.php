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

	public ?\Closure $errorHandler = null;

	/**
	 * PdoCacheRedis constructor.
	 *
	 * @param Client $redis
	 * @param int $cacheTime
	 */
	public function __construct( Client $redis, int $cacheTime = 600, ?\Closure $errorHandler = null ) {
		$this->cacheTime    = $cacheTime;
		$this->redis        = $redis;
		$this->errorHandler = $errorHandler;
	}

	/**
	 * @param \Error $error
	 */
	private function __handleError( \Error $error ) {
		if ( $this->errorHandler ) {
			call_user_func_array( $this->errorHandler, [ $error ] );
		} else {
			throw $error;
		}
	}

	/**
	 * @param string $key
	 *
	 * @return mixed|ResultsInterface|null
	 */
	public function read( string $key ) {
		$raw = $this->redis->get( $this->prefixKey( $key ) );
		if ( strlen( $raw ) ) {
			try {
				$raw  = gzinflate( $raw );
				$data = unserialize( $raw );
				unset( $raw );
				if ( $data instanceof ResultsInterface ) {
					$data->setFromCache( true );
				}
			} catch ( \Error $error ) {
				$this->__handleError( $error );
			}

			return $data;
		}

		return null;
	}

	public function scan( string $key ): array {
		$key     = rtrim( $key, "*" );
		$results = [];
		foreach ( new Keyspace( $this->redis, $this->prefixKey( $key ) . '*', 1000 ) as $matchKey ) {
			$results[] = $this->read( $matchKey );
		}

		return $results;
	}

	protected function prefixKey( $key ): string {
		return static::PREFIX . ltrim( $key, static::PREFIX );
	}

	public function scanAndClean( string $key ): array {
		$key     = rtrim( $key, "*" );
		$matches = [];
		$results = [];
		foreach ( new Keyspace( $this->redis, $this->prefixKey( $key ) . '*', 10000 ) as $matchKey ) {
			$matches[] = $this->prefixKey( $matchKey );
			$results[] = $this->read( $matchKey );
		}
		if ( $matches ) {
			$this->redis->del( $matches );
		}

		return $results;
	}

	public function write( string $key, $data ): bool {
		if ( $data instanceof PreSerialize ) {
			$data->__preSerialize();
		}

		return (bool) $this->redis->setex( $this->prefixKey( $key ), $this->cacheTime, gzdeflate( serialize( $data ) ) );
	}

	public function clean( string $key ): bool {
		return (bool) $this->redis->del( [ $this->prefixKey( $key ) ] );
	}

	public function runCleanPolicy( ShardDB $shardDb ): void {
		//do nothing
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function cleanAllMatching( string $key ): bool {
		$key     = rtrim( $key, "*" );
		$matches = [];
		foreach ( new Keyspace( $this->redis, $this->prefixKey( $key ) . '*', null ) as $matchKey ) {
			$matches[] = $this->prefixKey( $matchKey );
		}
		if ( $matches ) {
			$this->redis->del( $matches );

			return true;
		}

		return false;
	}
}