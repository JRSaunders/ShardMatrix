<?php


namespace ShardMatrix\GoThreaded;


use ShardMatrix\DB\NodeQueries;

/**
 * Class Client
 * @package ShardMatrix\GoThreaded
 */
class Client {
	/**
	 * @var
	 */
	protected $resource;
	/**
	 * @var string
	 */
	protected string $host;
	/**
	 * @var string
	 */
	protected string $username;
	/**
	 * @var string
	 */
	protected string $password;
	/**
	 * @var int
	 */
	protected int $port;
	/**
	 * @var int
	 */
	protected int $timeout;
	/**
	 * @var
	 */
	protected ?string $errorNumber = null;
	/**
	 * @var
	 */
	protected ?string $errorString = null;

	/**
	 * Client constructor.
	 *
	 * @param string $hostname
	 * @param int $port
	 * @param string $username
	 * @param string $password
	 * @param int $timeout
	 */
	public function __construct( string $hostname = '127.0.0.1', int $port = 1534, string $username = 'gothreaded', string $password = 'password', int $timeout = 30 ) {
		$this->host     = $hostname;
		$this->port     = $port;
		$this->timeout  = $timeout;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * @throws GoThreadedException
	 */
	private function connect() {
		if ( ! is_resource( $this->resource ) ) {
			$this->resource = @fsockopen( $this->host, $this->port, $this->errorNumber, $this->errorString, $this->timeout );

			if ( ! $this->resource ) {
				throw new GoThreadedException( $this->errorString, $this->errorNumber );
			}
		}
	}

	/**
	 * @return $this
	 * @throws GoThreadedException
	 */
	public function __killClient(): Client {
		$this->connect();
		fwrite( $this->resource, json_encode( [
			'auth' => [
				'username' => $this->username,
				'password' => $this->password
			],
			'kill' => 1
		] ) );

		return $this;
	}

	public function execQueries( NodeQueries $nodeQueries ): Client {
		$this->connect();
		fwrite( $this->resource, json_encode( [
			'auth'         => [
				'username' => $this->username,
				'password' => $this->password
			],
			'node_queries' => $nodeQueries
		] ) );

		return $this;
	}

	/**
	 * @return Results
	 * @throws GoThreadedException
	 */
	public function getResults(): Results {
		try {
			$results = new Results( json_decode( fgets( $this->resource ) ) );
			$this->close();

			return $results;
		} catch ( \Error | \Exception $e ) {
			throw new GoThreadedException( $e->getMessage(), $e->getCode() );
		}
	}

	public function close(): bool {
		if ( is_resource( $this->resource ) ) {
			return (bool) fclose( $this->resource );
		}

		return true;
	}

	public function __destruct() {
		$this->close();
	}

	/**
	 * @return int|null
	 */
	public function getErrorNumber(): ?int {
		return $this->errorNumber;
	}

	/**
	 * @return string|null
	 */
	public function getErrorString(): ?string {
		return $this->errorString;
	}
}