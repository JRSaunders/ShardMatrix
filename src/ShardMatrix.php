<?php

namespace ShardMatrix;


use ShardMatrix\DB\ShardDB;
use ShardMatrix\GoThreaded\Client;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ShardMatrix
 * @package ShardMatrix
 */
class ShardMatrix {

	/**
	 * @var string
	 */
	protected static string $pdoCachePath = '../../shard_matrix_cache';
	/**
	 * @var string
	 */
	protected static string $PdoCacheClass = PdoCache::class;
	/**
	 * @var string
	 */
	protected static string $NodeQueriesAsyncClass = NodeQueriesPcntlFork::class;


	protected static array $services = [];

	protected static array $serviceInstances = [];

	/**
	 * @var Config
	 */
	protected static Config $config;

	const TABLE_ALGO = 'adler32';

	/**
	 * @var string|null
	 */
	protected static ?string $geo = null;

	protected static bool $docker = false;

	private static function init() {
		static::setServiceClosure( PdoCacheInterface::class, function () {
			return new PdoCache();
		} );
		static::setServiceClosure( Client::class, function () {
			return new Client();
		} );
	}

	public static function clearServiceInstances() {
		static::$serviceInstances = [];
	}

	/**
	 * @param string|null $configPath
	 */
	public static function initFromYaml( string $configPath = null ) {
		static::$config = new Config( Yaml::parse( file_get_contents( $configPath ) ) );
		static::init();
	}

	/**
	 * @param string|null $yamlString
	 */
	public static function initFromYamlString( string $yamlString = null ) {
		static::$config = new Config( Yaml::parse( $yamlString ) );
		static::init();
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

	public static function useGoThreadedForAsyncQueries() {
		static::setNodeQueriesAsyncClass( NodeQueriesGoThreaded::class );
	}

	public static function usePhpForkingForAsyncQueries() {
		static::setNodeQueriesAsyncClass( NodeQueriesPcntlFork::class );
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

	/**
	 * @param string $service
	 * @param \Closure $closure
	 */
	public static function setServiceClosure( string $service, \Closure $closure ) {
		static::$services[ $service ] = $closure;
	}

	/**
	 * @param string $service
	 *
	 * @return object
	 * @throws Exception
	 */
	public static function getService( string $service ): object {
		if ( isset( static::$serviceInstances[ $service ] ) ) {
			return static::$serviceInstances[ $service ];
		}
		if ( isset( static::$services[ $service ] ) ) {
			return static::$serviceInstances[ $service ] = call_user_func( static::$services[ $service ] );
		}
		throw new Exception( 'Service ' . $service . ' Closure not set!' );
	}

	/**
	 * @return PdoCacheInterface
	 * @throws Exception
	 */
	public static function getPdoCacheService(): PdoCacheInterface {
		return static::getService( PdoCacheInterface::class );
	}

	/**
	 * @param \Closure $closure
	 */
	public static function setPdoCacheService( \Closure $closure ) {
		static::setServiceClosure( PdoCacheInterface::class, $closure );
	}

	/**
	 * @param \Closure $closure
	 */
	public static function setGoThreadedService( \Closure $closure ) {
		static::setServiceClosure( Client::class, $closure );
	}

	/**
	 * @return Client
	 * @throws Exception
	 */
	public static function getGoThreadedService(): Client {
		return static::getService( Client::class );
	}

	public static function db(): ShardDB {
		return new ShardDB();
	}


}