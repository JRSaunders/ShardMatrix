<?php


namespace ShardMatrix;


class Node {
	protected string $name;
	protected array $nodeData;
	protected ?TableGroups $tableGroups = null;

	public function __construct( string $name, array $nodeData ) {
		$this->name     = $name;
		$this->nodeData = $nodeData;
	}

	public function getDsn(): string {
		return $this->nodeData['dsn'];
	}

	public function getTableGroups(): TableGroups {
		if ( isset( $this->tableGroups ) ) {
			return $this->tableGroups;
		}
		$allGroups  = ShardMatrix::getConfig()->getTableGroups();
		$nodeGroups = [];
		foreach ( $this->nodeData['table_groups'] as $name ) {
			$nodeGroups[] = $allGroups->getTableGroupByName( $name );
		}

		return $this->tableGroups ?? ( $this->tableGroups = new TableGroups( $nodeGroups ) );
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}


}