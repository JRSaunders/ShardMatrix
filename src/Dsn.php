<?php


namespace ShardMatrix;

/**
 * Class Dsn
 * @package ShardMatrix
 */
class Dsn implements \JsonSerializable {

	protected string $dsn;
	protected ?DockerNetwork $dockerNetwork = null;

	/**
	 * Dsn constructor.
	 *
	 * @param string|null $dsn
	 * @param DockerNetwork|null $dockerNetwork
	 */
	public function __construct( ?string $dsn, ?DockerNetwork $dockerNetwork = null ) {
		$this->dsn           = $dsn ?? '';
		$this->dockerNetwork = $dockerNetwork;

	}

	/**
	 * @return string|null
	 */
	public function getDriver(): ?string {
		return ( $value = explode( ':', $this->dsn )[0] ) ? $value : null;
	}

	/**
	 * @param $key
	 *
	 * @return string|null
	 */
	public function getAttribute( $key ): ?string {
		if ( strpos( $this->dsn, $key . '=' ) === false ) {
			return null;
		}
		$internalValueParts = explode( $key . '=', $this->dsn );
		if ( isset( $internalValueParts[1] ) ) {
			$internalValue = $internalValueParts[1];
		} else {
			$internalValue = $internalValueParts[0];
		}
		if ( $value = explode( ';', $internalValue )[0] ) {
			return $value;
		}

		return null;
	}

	/**
	 * @return string|null
	 */
	public function getDbname(): ?string {
		return $this->getAttribute( 'dbname' );
	}

	/**
	 * @return string|null
	 */
	public function getUsername(): ?string {
		return $this->getAttribute( 'user' );
	}

	/**
	 * @return string|null
	 */
	public function getPassword(): ?string {
		return $this->getAttribute( 'password' );
	}

	/**
	 * @param bool $removePort
	 *
	 * @return string|null
	 */
	public function getHost( bool $removePort = true ): ?string {
		$value = $this->getAttribute( 'host' );
		if ( $removePort && $value ) {
			if ( strpos( $value, ':' ) !== false ) {
				$value = explode( ':', $value )[0];
			}
		}
		if ( $value ) {
			$value = str_replace( 'localhost', '127.0.0.1', $value );
		}

		return $value;
	}

	/**
	 * @return string|null
	 */
	public function getPort(): ?string {

		$port = $this->getAttribute( 'port' );
		$host = $this->getHost( false );
		if ( ! $port && strpos( $host, ':' ) !== false ) {
			return explode( ':', $host )[1];
		}

		return $port;
	}

	public function getCharacterSet( bool $returnDefault = true ): ?string {
		if ( $this->getDriver() == 'pgsql' ) {
			return ( $this->getAttribute( 'charset' ) ?? 'utf8' );
		} else {
			return ( $this->getAttribute( 'charset' ) ?? 'utf8mb4' );
		}
	}

	public function getCharacterSetString(): ?string {
		if ( $this->getDriver() == 'pgsql' ) {
			return "options='--client_encoding=" . $this->getCharacterSet() . "'";
		} else {
			return "charset=" . $this->getCharacterSet();
		}

	}

	/**
	 * @return string
	 */
	public function __toString() {
		$host = $this->getHost( true );
		$port = $this->getPort();
		if ( ShardMatrix::isDocker() ) {
			if ( $this->dockerNetwork && $this->dockerNetwork->getHost() ) {
				$host = $this->dockerNetwork->getHost();
			}
			if ( $this->dockerNetwork && $this->dockerNetwork->getHost() ) {
				$host = $this->dockerNetwork->getHost();
			}
		}

		return join( ';', [
			$this->getDriver() . ':host=' . $host,
			'port=' . $port,
			'dbname=' . $this->getDbname(),
			'user=' . $this->getUsername(),
			'password=' . $this->getPassword(),
			$this->getCharacterSetString()
		] );
	}


	public function jsonSerialize() {
		return [
			'driver'   => $this->getDriver(),
			'host'     => $this->getHost(),
			'port'     => $this->getPort(),
			'dbname'   => $this->getDbname(),
			'user'     => $this->getUsername(),
			'password' => $this->getPassword(),
			'charset'  => $this->getCharacterSet()
		];
	}
}