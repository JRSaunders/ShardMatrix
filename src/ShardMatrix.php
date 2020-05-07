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
	 * @var string
	 */
	protected static string $PdoCacheClass = PdoCache::class;

	protected static string $NodeQueriesAsyncClass = NodeQueriesPcntlFork::class;
	/**
	 * @var string|null
	 */
	protected static ?string $geo = null;

	protected static bool $docker = false;

	/**
	 * @param string|null $configPath
	 */
	public static function initFromYaml( string $configPath = null ) {
		static::$config = new Config( Yaml::parse( file_get_contents( $configPath ) ) );
	}

	/**
	 * @param string|null $yamlString
	 */
	public static function initFromYamlString( string $yamlString = null ) {
		static::$config = new Config( Yaml::parse( $yamlString ) );
	}

	/**
	 * @param string|null $json
	 */
	public static function initFromJson( string $json = null ) {
		static::$config = new Config( json_decode( $json ) );
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

	/**
	 * @return string
	 */
	public static function getPdoCacheClass(): string {
		return static::$PdoCacheClass;
	}

	/**
	 * @param string $PdoCacheClass
	 *
	 * @throws Exception
	 */
	public static function setPdoCacheClass( string $PdoCacheClass ): void {
		if ( in_array( PdoCacheInterface::class, class_implements( $PdoCacheClass ) ) ) {
			static::$PdoCacheClass = $PdoCacheClass;
		} else {
			throw new Exception( $PdoCacheClass . ' needs to implement ' . PdoCacheInterface::class );
		}

	}

	/**
	 * @return string
	 */
	public static function getNodeQueriesAsyncClass(): string {
		return static::$NodeQueriesAsyncClass;
	}

	/**
	 * @param string $NodeQueriesAsyncClass
	 *
	 * @throws Exception
	 */
	public static function setNodeQueriesAsyncClass( string $NodeQueriesAsyncClass ): void {
		if ( in_array( NodeQueriesAsyncInterface::class, class_implements( $NodeQueriesAsyncClass ) ) ) {
			static::$NodeQueriesAsyncClass = $NodeQueriesAsyncClass;
		} else {
			throw new Exception( $NodeQueriesAsyncClass . ' needs to implement ' . NodeQueriesAsyncInterface::class );
		}
	}

	/**
	 * @return bool
	 */
	public static function isDocker(): bool {
		return static::$docker;
	}

	/**
	 * @param bool $docker
	 */
	public static function setDocker( bool $docker ): void {
		static::$docker = $docker;
	}

}