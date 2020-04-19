<?php


namespace ShardMatrix;


class Config {
	protected array $configArray = [];
	protected ?TableGroups $tableGroups = null;
	protected ?Nodes $nodes = null;
	protected ?UniqueColumns $uniqueColumns = null;

	public function __construct( array $configArray ) {
		$this->configArray = $configArray;
	}

	public function getTableGroups(): TableGroups {
		return $this->tableGroups ?? ( $this->tableGroups = new TableGroups( $this->configArray['table_groups'] ) );
	}

	public function getNodes(): Nodes {
		return $this->nodes ?? ( $this->nodes = new Nodes( $this->configArray['nodes'] ) );
	}

	public function getUniqueColumns(): UniqueColumns {
		return $this->uniqueColumns ?? ( $this->uniqueColumns = new UniqueColumns( $this->configArray['unique_columns'] ) );
	}

}