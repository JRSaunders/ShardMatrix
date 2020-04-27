<?php


namespace ShardMatrix;


class TableGroup {
	/**
	 * @var Tables
	 */
	protected Tables $tables;
	/**
	 * @var string
	 */
	protected string $name;

	/**
	 * TableGroup constructor.
	 *
	 * @param string $name
	 * @param array $tables
	 */
	public function __construct( string $name, array $tables ) {
		$this->name = $name;
		$this->setTables( $tables );
	}

	/**
	 * @param array $tables
	 *
	 * @return $this
	 */
	public function setTables( array $tables ): TableGroup {
		$this->tables = new Tables( $tables );
		return $this;
	}

	/**
	 * @param $tableName
	 *
	 * @return bool
	 */
	public function containsTableName( $tableName ): bool {
		foreach ( $this->getTables() as $table ) {
			if ( $table->getName() == $tableName ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param $hash
	 *
	 * @return Table|null
	 */
	public function getTableByTableHash( $hash ): ?Table {
		foreach ( $this->getTables() as $table ) {
			if ( $table->getHash() == $hash ) {
				return $table;
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return Tables
	 */
	public function getTables(): Tables {
		return $this->tables;
	}
}