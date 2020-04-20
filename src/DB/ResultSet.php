<?php


namespace ShardMatrix\DB;

/**
 * Class ResultSet
 * @package ShardMatrix\DB
 */
class ResultSet implements \Iterator, \JsonSerializable {

	protected int $position = 0;

	protected array $resultSet = [];

	public function __construct( array $resultSet, string $resultRowReturnClass = ResultRow::class ) {
		$this->setResultSet( $resultSet, $resultRowReturnClass );
	}

	public function setResultSet( array $resultSet, string $resultRowReturnClass = ResultRow::class ) {
		foreach ( $resultSet as &$row ) {
			if ( ! $row instanceof ResultRow ) {
				$row = new $resultRowReturnClass( $row );
			}
		}
		$this->resultSet = $resultSet;
	}

	/**
	 * @return ResultRow[]
	 */
	public function getResultSet(): array {
		return $this->resultSet;
	}

	/**
	 * @return ResultRow
	 */
	public function current(): ResultRow {
		return $this->resultSet[ $this->position ];
	}

	public function next() {
		$this->position ++;
	}

	public function key() {
		return $this->position;
	}

	public function valid() {
		return isset( $this->resultSet[ $this->position ] );
	}

	public function rewind() {
		$this->position = 0;
	}


	public function jsonSerialize() {
		$array = [];
		foreach ( $this->getResultSet() as $result ) {
			$array[] = $result->__toObject();
		}

		return $array;
	}
}