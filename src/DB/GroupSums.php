<?php


namespace ShardMatrix\DB;

use mysql_xdevapi\RowResult;

/**
 * Class GroupSums
 * @package ShardMatrix\DB
 */
class GroupSums extends DataRows {
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
	public function getDataRows(): array {
		return parent::getDataRows();
	}

	/**
	 * @param array $resultSet
	 * @param string $resultRowReturnClass
	 */
	public function setDataRows( array $resultSet, string $resultRowReturnClass = GroupSum::class ) {
		foreach ( $resultSet as &$row ) {
			if ( ! $row instanceof GroupSum ) {
				$row = new $resultRowReturnClass( $row );
			}
		}
		$this->dataRows = $resultSet;
	}
	/**
	 * @return GroupSum
	 */
	public function current() {
		return $this->dataRows[ $this->position ];
	}

	/**
	 * @return int
	 */
	public function getTotalSum(): int {
		$sum = 0;
		foreach ( $this->getDataRows() as $groupSum ) {
			$sum = $sum + $groupSum->getSum();
		}

		return $sum;
	}
}