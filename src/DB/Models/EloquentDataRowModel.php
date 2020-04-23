<?php

namespace ShardMatrix\DB\Models;

use Illuminate\Database\Eloquent\Model;
use ShardMatrix\Db\Builder\DB;
use ShardMatrix\DB\Exception;
use ShardMatrix\DB\Interfaces\ConstructArrayInterface;
use ShardMatrix\DB\Interfaces\DBDataRowTransactionsInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\Uuid;

/**
 * Class EloquentDataRowModel
 * @package ShardMatrix\DB\Models
 */
class EloquentDataRowModel extends Model implements DBDataRowTransactionsInterface, ConstructArrayInterface {
	protected array $uuids = [];
	protected $guarded = [ '/' ];

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
				} else {
					$resultArray[] = $this->uuids[ $name ] = new Uuid( $this->attributes[ $name ] );
				}
			}
		}

		return $resultArray;
	}

	public function __toObject(): \stdClass {
		return (object) $this->attributes;
	}

	public function __toArray(): array {
		return $this->attributes;
	}

	public function __setRowData( \stdClass $row ) {
		$this->attributes = (array) $row;
	}

	public function save(array $options = []) {
		$uuid  = $this->getUuid();
		$array = $this->__toArray();
		if ( isset( $array['modified'] ) ) {
			$array['modified'] = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
		}

		unset( $array['uuid'] );

		return (bool) DB::table( $uuid->getTable()->getName() )->whereUuid( $uuid )->update( $array );
	}

	public function delete() {
		$uuid = $this->getUuid();

		return (bool) DB::table( $uuid->getTable()->getName() )->delete( $uuid );
	}

	/**
	 * @return Uuid|null
	 * @throws Exception
	 * @throws \ShardMatrix\Exception
	 */
	public function create(): ?Uuid {
		if ( ! isset( $this->table ) ) {
			throw new Exception( 'table needs to be set' );
		}

		return DB::table( $this->table )->insert( $this->__toArray() );
	}
}