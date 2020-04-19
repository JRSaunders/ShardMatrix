<?php


namespace ShardMatrix;


class Node {

	protected string $name;
	protected array $nodeData;
	protected ?TableGroups $tableGroups = null;
	protected ?string $lastUsedTableName = null;

	public function __construct( string $name, array $nodeData ) {
		$this->name     = $name;
		$this->nodeData = $nodeData;
	}

	public function getDsn(): string {
		return $this->nodeData['dsn'];
	}

	public function getPassword(): string {
		return $this->nodeData['password'];
	}

	public function getUsername(): string {
		return $this->nodeData['username'];
	}

	public function isInsertData(): bool {
		if ( isset( $this->nodeData['insert_data'] ) && $this->nodeData['insert_data'] == 'false' ) {
			return false;
		}

		return true;
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

	public function containsTableName( $tableName ): bool {

		foreach ( $this->getTableGroups() as $group ) {
			if ( $group->containsTableName( $tableName ) ) {
				return true;
			}
		}


		return false;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @param string|null $lastUsedTableName
	 *
	 * @return Node
	 */
	public function setLastUsedTableName( ?string $lastUsedTableName ): Node {
		$this->lastUsedTableName = $lastUsedTableName;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getLastUsedTableName(): ?string {
		return $this->lastUsedTableName;
	}


}