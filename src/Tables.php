<?php


namespace ShardMatrix;


/**
 * Class Tables
 * @package ShardMatrix
 */
class Tables implements \Iterator {

	protected $position = 0;
	/**
	 * @var Table[]
	 */
	protected $tables = [];

	/**
	 *
	 * Tables constructor.
	 *
	 * @param array $tables
	 */
	public function __construct( array $tables ) {
		$this->setTables( $tables );
	}

	/**
	 * @return Table[]
	 */
	public function getTables(): array {

		return $this->tables;
	}

	/**
	 * @param array $tables
	 *
	 * @return Tables
	 */
	public function setTables( array $tables ): Tables {
		foreach ( $tables as $name ) {
			if ( $name instanceof Table ) {
				$this->tables[] = $name;
			} else {
				$this->tables[] = new Table( $name );
			}
		}

		return $this;
	}


	public function countTables(): int {
		return count( $this->getTables() );
	}


	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return Table Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		return $this->getTables()[ $this->position ];
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
		return isset( $this->tables[ $this->position ] );
	}
}