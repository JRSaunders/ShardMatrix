<?php


namespace ShardMatrix;

/**
 * Class DockerNetwork
 * @package ShardMatrix
 */
class DockerNetwork implements \JsonSerializable {
	protected ?string $host = null;
	protected ?string $port = null;
	protected ?string $dockerNetwork = null;

	/**
	 * DockerNetwork constructor.
	 *
	 * @param string|null $dockerNetwork
	 */
	public function __construct( ?string $dockerNetwork ) {
		$this->dockerNetwork = $dockerNetwork;
		if ( $dockerNetwork ) {
			$parts = explode( ':', $dockerNetwork );
			if ( isset( $parts[1] ) ) {
				$this->port = $parts[1];
			}
			if ( isset( $parts[0] ) ) {
				$this->host = $parts[0];
			}
		}
	}

	/**
	 * @return string|null
	 */
	public function getHost(): ?string {
		return $this->host;
	}

	/**
	 * @return string|null
	 */
	public function getPort(): ?string {
		return $this->port;
	}

	public function jsonSerialize() {
		return [
			'port' => $this->port,
			'host' => $this->host
		];
	}
}