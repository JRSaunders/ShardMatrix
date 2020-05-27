<?php


namespace ShardMatrix;

use ShardMatrix\DB\Interfaces\PreSerialize;
use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\DB\ShardDB;

/**
 * Class PdoCacheMemcached
 * @package ShardMatrix
 */
class PdoCacheMemcached implements PdoCacheInterface {

	protected \Memcached $memcached;
	/**
	 * @var int
	 */
	protected int $cacheTime;

	public function __construct( \Memcached $memcached, int $cacheTime = 600 ) {
		$this->cacheTime = $cacheTime;
		$this->memcached = $memcached;
	}

	protected function prefixKey( $key ): string {
		return static::PREFIX . ltrim( $key, static::PREFIX );
	}

	public function read( string $key ) {
		$raw = $this->memcached->get( $this->prefixKey( $key ) );
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
		$results = [];
		$keys    = $this->memcached->get( $this->prefixKey( $key ) );
		if ( is_array( $keys ) ) {
			foreach ( $keys as $key ) {
				$results[] = $this->read( $key );
			}
		}

		return $results;
	}

	public function scanAndClean( string $key ): array {
		$results = [];
		$keys    = $this->memcached->get( $this->prefixKey( $key ) );
		if ( is_array( $keys ) ) {
			foreach ( $keys as $key ) {
				$results[] = $this->read( $key );
			}
			$this->memcached->deleteMulti( $keys );
		}

		return $results;
	}

	public function write( string $key, $data ): bool {
		if ( $data instanceof PreSerialize ) {
			$data->__preSerialize();
		}
		$this->setKeysArray( $key );

		return (bool) $this->memcached->set( $this->prefixKey( $key), gzdeflate( serialize( $data ) ), $this->cacheTime );
	}

	/**
	 * @param $key
	 */
	protected function setKeysArray( $key ) {
		$splitKey = function ( $keySplit, $delimiter, $key ) {
			$partKey = '';
			$i       = 0;
			foreach ( $keySplit as $part ) {
				$i ++;
				$partKey .= $part;
				if ( $i == count( $keySplit ) ) {
					$partKey .= $delimiter;
				}
				$existingKeys = $this->memcached->get( $this->prefixKey( $partKey ));
				if ( ! is_array( $existingKeys ) ) {
					$existingKeys = [];
				}

				$existingKeys[] = $key;

				$this->memcached->set( $this->prefixKey( $partKey), array_unique( $existingKeys ), $this->cacheTime );
			}
		};
		$splitKey( explode( '-', $key ), '-', $key );
		$splitKey( explode( ':', $key ), ':', $key );

	}

	public function clean( string $key ): bool {
		return (bool) $this->memcached->delete( $this->prefixKey( $key ) );
	}

	public function runCleanPolicy( ShardDB $shardDb ): void {
		//do nothing
	}
}