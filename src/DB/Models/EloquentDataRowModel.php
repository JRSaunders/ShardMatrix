<?php


use ShardMatrix\Uuid;

class EloquentDataRowModel extends \Illuminate\Database\Eloquent\Model implements \ShardMatrix\DB\Interfaces\ShardDataRowInterface {
	public array $uuids = [];

	public function __columnIsset( $column ): bool {
		return isset( $this->attributes[ $column ] );
	}

	public function getUuid(): ?\ShardMatrix\Uuid {
		if ( isset( $this->uuids['uuid'] ) ) {
			return $this->uuids['uuid'];
		}
		if ( isset( $this->attributes['uuid'] ) ) {
			return $this->uuids['uuid'] = new Uuid( $this->attributes['uuid'] );
		}

		return null;
	}

	public function getJoinUuids(): array {
		$resultArray = [];
		foreach ( $this->attributes as $name => $value ) {
			if ( strpos( $name, '_uuid' ) !== false ) {
				if ( isset( $this->uuids[ $name ] ) ) {
					$resultArray[] = $this->uuids[ $name ];
				}else {
					$resultArray[] = $this->uuids[ $name ] = new Uuid( $this->attributes[ $name ] );
				}
			}
		}

		return $resultArray;
	}

	public function __toObject(): \stdClass {
		// TODO: Implement __toObject() method.
	}

	public function __toArray(): array {
		// TODO: Implement __toArray() method.
	}
}