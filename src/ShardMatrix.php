<?php

namespace ShardMatrix;


use Symfony\Component\Yaml\Yaml;

class ShardMatrix {

	protected static Config $config;

	const TABLE_ALGO = 'adler32';

	public static function initFromYaml( string $configPath = null ) {
		static::$config = new Config( Yaml::parse( file_get_contents( $configPath ) ) );
	}

	public static function getConfig(): Config {
		return static::$config;
	}


}