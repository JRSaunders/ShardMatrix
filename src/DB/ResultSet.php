<?php


namespace ShardMatrix\DB;

/**
 * Class ResultSet
 * @package ShardMatrix\DB
 */
class ResultSet implements \Iterator {

	protected int $position = 0;

	protected array $resultSet = [];

	public function __construct( array $resultSet ) {
		$this->setResultSet( $resultSet );
	}

	public function setResultSet( array $resultSet ) {
		foreach ( $resultSet as &$row ) {
			if ( ! $row instanceof ResultRow ) {
				$row = new ResultRow( $row );
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
}