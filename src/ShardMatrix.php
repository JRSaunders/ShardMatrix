<?php

namespace ShardMatrix;


use Symfony\Component\Yaml\Yaml;

class ShardMatrix {

	protected static Config $config;

	const TABLE_ALGO = 'adler32';

	protected static string $pdoCachePath = '../../shard_matrix_cache';

	public static function initFromYaml( string $configPath = null ) {
		static::$config = new Config( Yaml::parse( file_get_contents( $configPath ) ) );
	}

	public static function getConfig(): Config {
		return static::$config;
	}

	public static function setPdoCachePath( string $pdoCachePath ) {
		if ( ! is_dir( $pdoCachePath ) ) {
			mkdir( $pdoCachePath, 0775 );
		}
		static::$pdoCachePath = $pdoCachePath;
	}

	public static function getPdoCachePath(): string {
		return static::$pdoCachePath;
	}

}