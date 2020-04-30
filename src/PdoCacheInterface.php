<?php


namespace ShardMatrix;


interface PdoCacheInterface {
	public function read( string $key );

	public function scan( string $key ): array;

	public function scanAndClean( string $key ): array;

	public function write( string $key, string $data ): bool;

	public function clean( string $key ): bool;
}