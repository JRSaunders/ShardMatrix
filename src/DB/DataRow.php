<?php


namespace ShardMatrix\DB;


use ShardMatrix\Uuid;

/**
 * Class DataRow
 * @package ShardMatrix\DB
 */
class DataRow implements \JsonSerializable {

	protected \stdClass $row;
	protected $uuids = [];

	public function __construct( \stdClass $row ) {
		$this->row = $row;
	}

	/**
	 * @param $column
	 *
	 * @return bool
	 */
	final public function __columnIsset( $column ): bool {
		return isset( $this->row->$column );
	}

	/**
	 * @return Uuid|null
	 */
	final public function getUuid(): ?Uuid {
		if ( isset( $this->uuids['uuid'] ) ) {
			return $this->uuids['uuid'];
		}
		if ( isset( $this->row->uuid ) ) {
			return $this->uuids['uuid'] = new Uuid( $this->row->uuid );
		}

		return null;
	}

	/**
	 * @return Uuid[]
	 */
	final public function getJoinUuids(): array {
		$resultArray = [];
		foreach ( $this->row as $name => $value ) {
			if ( strpos( $name, '_uuid' ) !== false ) {
				if ( isset( $this->uuids[ $name ] ) ) {
					$resultArray[] = $this->uuids[ $name ];
				}
				$resultArray[] = $this->uuids[ $name ] = new Uuid( $this->row->$name );
			}
		}

		return $resultArray;
	}

	/**
	 * @param $name
	 *
	 * @return mixed|Uuid|null
	 */
	final public function __get( $name ) {
		if ( strpos( $name, 'uuid' ) !== false && isset( $this->row->$name ) ) {
			if ( isset( $this->uuids[ $name ] ) ) {
				return $this->uuids[ $name ];
			}

			return $this->uuids[ $name ] = new Uuid( $this->row->$name );
		}
		if ( isset( $this->row->$name ) ) {
			return $this->row->$name;
		}

		return null;
	}

	public function __set( $name, $value ) {
		if ( $name != 'uuid' ) {
			$this->row->$name = $value;
		}
	}

	public function __toObject(): \stdClass {
		return $this->row;
	}

	public function __toArray(): array {
		return (array) $this->row;
	}

	public function jsonSerialize() {
		return $this->__toObject();
	}
}