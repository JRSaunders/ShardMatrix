<?php


namespace ShardMatrix;


class UniqueColumns implements \Iterator {

	protected $position = 0;
	/**
	 * @var string[]
	 */
	protected $uniqueColumns = [];

	/**
	 * UniqueColumns constructor.
	 *
	 * @param array $UniqueColumns
	 */
	public function __construct( array $uniqueColumns ) {
		$this->uniqueColumns = $uniqueColumns;
	}

	/**
	 * @return UniqueColumn[]
	 */
	public function getUniqueColumns(): array {

		return $this->uniqueColumns;
	}

	/**
	 * @param string $tableName
	 *
	 * @return array
	 */
	public function getUniqueColumnByTableName( string $tableName ): array {

		foreach ( $this->getUniqueColumns() as $tableNameKey => $uniqueColumns ) {
			if ( $tableName == $tableNameKey ) {
				return $uniqueColumns;
			}
		}

		return [];
	}


	public function countUniqueColumns(): int {
		return count( $this->getUniqueColumns() );
	}


	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return UniqueColumn Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		return $this->getUniqueColumns()[ $this->position ];
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
		return isset( $this->uniqueColumns[ $this->position ] );
	}

}