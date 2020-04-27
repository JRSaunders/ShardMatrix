<?php


namespace ShardMatrix;

/**
 * Class Config
 * @package ShardMatrix
 */
class Config {
	/**
	 * @var array
	 */
	protected array $configArray = [];
	/**
	 * @var TableGroups|null
	 */
	protected ?TableGroups $tableGroups = null;
	/**
	 * @var Nodes|null
	 */
	protected ?Nodes $nodes = null;
	/**
	 * @var UniqueColumns|null
	 */
	protected ?UniqueColumns $uniqueColumns = null;

	/**
	 * Config constructor.
	 *
	 * @param array $configArray
	 */
	public function __construct( array $configArray ) {
		$this->configArray = $configArray;
	}

	/**
	 * @return TableGroups
	 */
	public function getTableGroups(): TableGroups {
		return $this->tableGroups ?? ( $this->tableGroups = new TableGroups( $this->configArray['table_groups'] ) );
	}

	/**
	 * @return Nodes
	 */
	public function getNodes(): Nodes {
		return $this->nodes ?? ( $this->nodes = new Nodes( $this->configArray['nodes'] ) );
	}

	/**
	 * @return UniqueColumns
	 */
	public function getUniqueColumns(): UniqueColumns {
		return $this->uniqueColumns ?? ( $this->uniqueColumns = new UniqueColumns( $this->configArray['unique_columns'] ) );
	}

}