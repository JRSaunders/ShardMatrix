<?php


namespace ShardMatrix;


use ShardMatrix\DB\ShardDB;

interface PdoCacheInterface {
	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function read( string $key );

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function scan( string $key ): array;

	/**
	 * @param string $key
	 *
	 * @return array
	 */
	public function scanAndClean( string $key ): array;

	/**
	 * @param string $key
	 * @param string $data
	 *
	 * @return bool
	 */
	public function write( string $key, string $data ): bool;

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function clean( string $key ): bool;

	/**
	 * @param ShardDB $shardDb
	 */
	public function runCleanPolicy( ShardDB $shardDb ): void;
}