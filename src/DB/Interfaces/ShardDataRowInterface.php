<?php


namespace ShardMatrix\DB\Interfaces;


use ShardMatrix\Uuid;

/**
 * Interface ShardDataRowInterface
 * @package ShardMatrix\DB\Interfaces
 */
interface ShardDataRowInterface extends \JsonSerializable {
	/**
	 * ShardDBRowInterface constructor.
	 *
	 * @param \stdClass $row
	 */
	public function __setRowData( \stdClass $row );

	/**
	 * @param $column
	 *
	 * @return bool
	 */
	public function __columnIsset( $column ): bool;

	/**
	 * @return Uuid|null
	 */
	public function getUuid(): ?Uuid;

	/**
	 * @return Uuid[]
	 */
	public function getJoinUuids(): array;

	/**
	 * @param $name
	 *
	 * @return mixed|Uuid|null
	 */
	public function __get( $name );

	public function __set( $name, $value );

	public function __toObject(): \stdClass;

	public function __toArray(): array;

	public function jsonSerialize();
}