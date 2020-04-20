<?php


namespace ShardMatrix\DB;

use mysql_xdevapi\RowResult;

/**
 * Class GroupSumSet
 * @package ShardMatrix\DB
 */
class GroupSumSet extends ResultSet {
	/**
	 * GroupSumSet constructor.
	 *
	 * @param array $resultSet
	 * @param string $resultRowReturnClass
	 */
	public function __construct( array $resultSet, string $resultRowReturnClass = GroupSum::class ) {
		parent::__construct( $resultSet, $resultRowReturnClass );
	}

	/**
	 * @return GroupSum[]
	 */
	public function getResultSet(): array {
		return parent::getResultSet();
	}

	/**
	 * @param array $resultSet
	 * @param string $resultRowReturnClass
	 */
	public function setResultSet( array $resultSet, string $resultRowReturnClass = GroupSum::class ) {
		foreach ( $resultSet as &$row ) {
			if ( ! $row instanceof GroupSum ) {
				$row = new $resultRowReturnClass( $row );
			}
		}
		$this->resultSet = $resultSet;
	}
	/**
	 * @return GroupSum
	 */
	public function current() {
		return $this->resultSet[ $this->position ];
	}

	/**
	 * @return int
	 */
	public function getTotalSum(): int {
		$sum = 0;
		foreach ( $this->getResultSet() as $groupSum ) {
			$sum = $sum + $groupSum->getSum();
		}

		return $sum;
	}
}