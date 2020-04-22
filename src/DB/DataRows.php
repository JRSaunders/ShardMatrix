<?php


namespace ShardMatrix\DB;

/**
 * Class DataRows
 * @package ShardMatrix\DB
 */
class DataRows implements \Iterator, \JsonSerializable {

	protected int $position = 0;

	protected array $dataRows = [];

	/**
	 * ResultSet constructor.
	 *
	 * @param array $resultSet
	 * @param string $resultRowReturnClass
	 */
	public function __construct( array $resultSet, string $resultRowReturnClass = DataRow::class ) {
		$this->setDataRows( $resultSet, $resultRowReturnClass );
	}

	/**
	 * @param array $resultSet
	 * @param string $resultRowReturnClass
	 */
	public function setDataRows( array $resultSet, string $resultRowReturnClass = DataRow::class ) {
		foreach ( $resultSet as &$row ) {
			if ( ! $row instanceof DataRow ) {
				$row = new $resultRowReturnClass( $row );
			}
		}
		$this->dataRows = $resultSet;
	}

	/**
	 * @return DataRow[]
	 */
	public function getDataRows(): array {
		return $this->dataRows;
	}

	/**
	 * @return DataRow
	 */
	public function current() {
		return $this->dataRows[ $this->position ];
	}

	public function next() {
		$this->position ++;
	}

	public function key() {
		return $this->position;
	}

	public function valid() {
		return isset( $this->dataRows[ $this->position ] );
	}

	public function rewind() {
		$this->position = 0;
	}


	public function jsonSerialize() {
		$array = [];
		foreach ( $this->getDataRows() as $result ) {
			$array[] = $result->__toObject();
		}

		return $array;
	}
}