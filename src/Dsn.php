<?php


namespace ShardMatrix;

/**
 * Class Dsn
 * @package ShardMatrix
 */
class Dsn {

	public string $dsn;

	/**
	 * Dsn constructor.
	 *
	 * @param string $dsn
	 */
	public function __construct( ?string $dsn ) {
		$this->dsn = $dsn ?? '';
	}

	/**
	 * @return string|null
	 */
	public function getConnectionType(): ?string {
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

	public function getCharacterSet(): ?string {
		return $this->getAttribute( 'charset' );
	}

	public function getCharacterSetString(): ?string {
		if ( $this->getConnectionType() == 'pgsql' ) {
			return "options='--client_encoding=" . ( $this->getCharacterSet() ?? 'utf8' ) . "'";
		} else {
			return "charset=" . ( $this->getCharacterSet() ?? 'utf8mb4' );
		}

	}

	/**
	 * @return string
	 */
	public function __toString() {

		return join( ';', [
			$this->getConnectionType() . ':host=' . $this->getHost( true ),
			'port=' . $this->getPort(),
			'dbname=' . $this->getDbname(),
			'user=' . $this->getUsername(),
			'password=' . $this->getPassword(),
			$this->getCharacterSetString()
		] );
	}
}