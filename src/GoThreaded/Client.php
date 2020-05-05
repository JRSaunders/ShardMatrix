<?php


namespace ShardMatrix\GoThreaded;


use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\ShardMatrixStatements;

class Client {
	protected $resource;
	protected string $host;
	protected int $port;
	protected int $timeout;
	protected $errorNumber;
	protected $errorString;

	public function __construct( string $hostname = 'localhost', int $port = 1534, int $timeout ) {
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
}