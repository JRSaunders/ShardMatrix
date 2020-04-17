<?php


namespace ShardMatrix;


class TableGroup {

	protected Tables $tables;
	protected string $name;

	public function __construct( string $name, array $tables ) {
		$this->name = $name;
		$this->setTables( $tables );
	}

	public function setTables( array $tables ) {
		$this->tables = new Tables( $tables);
	}

	public function containsTableName( $tableName ): bool {
		foreach ( $this->getTables() as $table ) {
			if ( $table->getName() == $tableName ) {
				return true;
			}
		}

		return false;
	}


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