<?php


namespace ShardMatrix\DB\Builder;


use ShardMatrix\DB\DataRow;

class Model extends DataRow {

	public function save(): bool {
		$uuid = $this->getUuid();
		if ( isset( $this->row->modified ) ) {
			$this->row->modified = ( new \DateTime() )->format( 'Y-m-d H:i:s' );
		}

		return (bool) DB::table( $uuid->getTable()->getName() )->whereUuid( $uuid )->update( $this->saveArray() );
	}

	public function delete(): bool {
		$uuid = $this->getUuid();

		return (bool) DB::table( $uuid->getTable()->getName() )->delete( $uuid );
	}

	protected function saveArray(): array {
		$array = $this->__toArray();
		unset( $array['uuid'] );

		return $array;
	}
}