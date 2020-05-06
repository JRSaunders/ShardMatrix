<?php


namespace ShardMatrix\DB;


use ShardMatrix\DB\Interfaces\ConstructArrayInterface;
use ShardMatrix\DB\Interfaces\ConstructObjectInterface;
use ShardMatrix\DB\Interfaces\CreatorInterface;
use ShardMatrix\DB\Interfaces\ShardDataRowInterface;

/**
 * Class DataRowFactory
 * @package ShardMatrix\DB
 */
class DataRowFactory implements CreatorInterface {
	protected $rowData;
	protected string $rowReturnClass;

	/**
	 * DataRowFactory constructor.
	 *
	 * @param $rowData
	 * @param string $rowReturnClass
	 */
	public function __construct( $rowData, string $rowReturnClass = DataRow::class ) {
		$this->rowData        = $rowData;
		$this->rowReturnClass = $rowReturnClass;
	}

	public function create() {
		$row            = $this->rowData;
		$rowReturnClass = $this->rowReturnClass;
		if ( ! $row instanceof ShardDataRowInterface ) {
			if ( in_array( ConstructObjectInterface::class, class_implements( $rowReturnClass ) ) ) {
				$row = new $rowReturnClass( (object) $row );
			}
			if ( in_array( ConstructArrayInterface::class, class_implements( $rowReturnClass ) ) ) {
				$row = new $rowReturnClass( (array) $row );
			}
		}

		return $row;
	}
}