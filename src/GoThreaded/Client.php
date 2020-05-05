<?php


namespace ShardMatrix\GoThreaded;


use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\ShardMatrixStatements;

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
	protected $errorNumber;
	/**
	 * @var
	 */
	protected $errorString;

	/**
	 * Client constructor.
	 *
	 * @param string $hostname
	 * @param int $port
	 * @param int $timeout
	 *
	 * @throws GoThreadedException
	 */
	public function __construct( string $hostname = 'localhost', int $port = 1534, int $timeout = 30) {
		$this->host    = $hostname;
		$this->port    = $port;
		$this->timeout = $timeout;
		$this->connect();
	}

	/**
	 * @throws GoThreadedException
	 */
	private function connect() {
		$this->resource = fsockopen( $this->hostname, $this->port, $this->errorNumber, $this->errorString, $this->timeout );

		if ( ! $this->resource ) {
			throw new GoThreadedException( $this->errorString, $this->errorNumber );
		}
	}

	public function execQueries( NodeQueries $nodeQueries ): Client {
		fwrite( $this->resource, json_encode( [ 'node_queries' => $nodeQueries ] ) );

		return $this;
	}

	public function getResults(): Results {
		return new Results( json_decode( fgets( $this->resource ) ) );

	}

	public function close(): bool {
		return (bool) fclose( $this->resource );
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