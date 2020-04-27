<?php

namespace ShardMatrix;


use Symfony\Component\Yaml\Yaml;

/**
 * Class ShardMatrix
 * @package ShardMatrix
 */
class ShardMatrix {
	/**
	 * @var Config
	 */
	protected static Config $config;

	const TABLE_ALGO = 'adler32';
	/**
	 * @var string
	 */
	protected static string $pdoCachePath = '../../shard_matrix_cache';
	/**
	 * @var string|null
	 */
	protected static ?string $geo = null;

	/**
	 * @param string|null $configPath
	 */
	public static function initFromYaml( string $configPath = null ) {
		static::$config = new Config( Yaml::parse( file_get_contents( $configPath ) ) );
	}

	/**
	 * @return Config
	 */
	public static function getConfig(): Config {
		return static::$config;
	}

	/**
	 * @param string $pdoCachePath
	 */
	public static function setPdoCachePath( string $pdoCachePath ) {
		if ( ! is_dir( $pdoCachePath ) ) {
			mkdir( $pdoCachePath, 0775 );
		}
		static::$pdoCachePath = $pdoCachePath;
	}

	/**
	 * @return string
	 */
	public static function getPdoCachePath(): string {
		return static::$pdoCachePath;
	}

	/**
	 * @param string|null $geo
	 */
	public static function setGeo( ?string $geo ): void {
		static::$geo = $geo;
	}

	/**
	 * @return string|null
	 */
	public static function getGeo(): ?string {
		return static::$geo;
	}

}