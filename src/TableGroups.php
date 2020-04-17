<?php


namespace ShardMatrix;


/**
 * Class TableGroups
 * @package TableGroupsModulus
 */
class TableGroups implements \Iterator {

	protected $position = 0;
	/**
	 * @var TableGroup[]
	 */
	protected $tableGroups = [];

	/**
	 * TableGroups constructor.
	 *
	 * @param array $TableGroups
	 */
	public function __construct( array $TableGroups ) {
		$this->setTableGroups( $TableGroups );
	}

	/**
	 * @return TableGroup[]
	 */
	public function getTableGroups(): array {

		return $this->tableGroups;
	}

	/**
	 * @param string $tableName
	 *
	 * @return TableGroup|null
	 */
	public function getTableGroupByTableName( string $tableName ): ?TableGroup {

		foreach ( $this->getTableGroups() as $group ) {
			if ( $group->containsTableName( $tableName ) ) {
				return $group;
			}
		}

		return null;
	}

	/**
	 * @param string $name
	 *
	 * @return TableGroup|null
	 */
	public function getTableGroupByName( string $name ): ?TableGroup {

		foreach ( $this->getTableGroups() as $group ) {
			if ( $group->getName() == $name ) {
				return $group;
			}
		}

		return null;
	}


	/**
	 * @param array $tableGroups
	 *
	 * @return TableGroups
	 */
	public function setTableGroups( array $tableGroups ): TableGroups {
		foreach ( $tableGroups as $name => $tableGroup ) {
			if ( $tableGroup instanceof TableGroup ) {
				$this->tableGroups[] = $tableGroup;
			} else {
				$this->tableGroups[] = new TableGroup( $name, $tableGroup );
			}
		}

		return $this;
	}


	public function countTableGroups(): int {
		return count( $this->getTableGroups() );
	}


	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return TableGroup Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		return $this->getTableGroups()[ $this->position ];
	}

	/**
	 * Move forward to next element
	 * @link https://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function next() {
		$this->position ++;
	}

	/**
	 * Return the key of the current element
	 * @link https://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 * @since 5.0.0
	 */
	public function key() {
		return $this->position;
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link https://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 * @since 5.0.0
	 */
	public function rewind() {
		$this->position = 0;
	}


	public function valid() {
		return isset( $this->tableGroups[ $this->position ] );
	}

	public function getTableByHash( string $hash ): ?Table {
		foreach ( $this->getTableGroups() as $group ) {
			foreach ( $group->getTables() as $table ) {
				if ( $table->getHash() == $hash ) {
					return $table;
				}
			}
		}

		return null;
	}
}