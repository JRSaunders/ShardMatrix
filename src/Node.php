<?php


namespace ShardMatrix;

/**
 * Class Node
 * @package ShardMatrix
 */
class Node implements \JsonSerializable {
	/**
	 * @var string
	 */
	protected string $name;
	/**
	 * @var array
	 */
	protected array $nodeData;
	/**
	 * @var TableGroups|null
	 */
	protected ?TableGroups $tableGroups = null;
	/**
	 * @var string|null
	 */
	protected ?string $lastUsedTableName = null;

	/**
	 * Node constructor.
	 *
	 * @param string $name
	 * @param array $nodeData
	 */
	public function __construct( string $name, array $nodeData ) {
		$this->name     = $name;
		$this->nodeData = $nodeData;
	}

	/**
	 * @return Dsn
	 */
	public function getDsn(): Dsn {
		return new Dsn( $this->nodeData['dsn'] ?? null );
	}

	/**
	 * @return string|null
	 */
	public function getGeo(): ?string {
		return $this->nodeData['geo'] ?? null;
	}

	/**
	 * @return bool
	 */
	public function isInsertData(): bool {
		if ( isset( $this->nodeData['insert_data'] ) && $this->nodeData['insert_data'] == 'false' ) {
			return false;
		}

		return true;
	}

	/**
	 * @return TableGroups
	 */
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
	 * @param $tableName
	 *
	 * @return bool
	 */
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


	public function jsonSerialize() {
		return [
			'name' => $this->getName(),
			'dsn' => $this->getDsn(),
			'geo' => $this->getGeo()
		];
	}
}