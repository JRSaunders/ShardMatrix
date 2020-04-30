<?php


namespace ShardMatrix;


/**
 * Class Nodes
 * @package ShardMatrix
 */
class Nodes implements \Iterator {
	/**
	 * @var int
	 */
	protected $position = 0;
	/**
	 * @var Node[]
	 */
	protected $nodes = [];

	/**
	 * Nodes constructor.
	 *
	 * @param array $nodes
	 */
	public function __construct( array $nodes ) {
		$this->setNodes( $nodes );
	}

	/**
	 * @return Node[]
	 */
	public function getNodes(): array {

		return $this->nodes;
	}

	/**
	 * @return Node[]
	 */
	public function getInsertNodes(): array {
		$returnArray = [];
		foreach ( $this->getNodes() as $node ) {
			if ( $node->isInsertData() ) {
				$returnArray[] = $node;
			}
		}

		return $returnArray;
	}

	/**
	 * @param array $nodes
	 *
	 * @return Nodes
	 */
	public function setNodes( array $nodes ): Nodes {
		foreach ( $nodes as $name => $data ) {
			if ( $data instanceof Node ) {
				$this->nodes[] = $data;
			} else {
				$this->nodes[] = new Node( $name, $data );
			}
		}

		return $this;
	}

	/**
	 * @return int
	 */
	public function countNodes(): int {
		return count( $this->getNodes() );
	}

	/**
	 * @return int
	 */
	public function countInsertNodes(): int {
		return count( $this->getInsertNodes() );
	}


	/**
	 * Return the current element
	 * @link https://php.net/manual/en/iterator.current.php
	 * @return Node Can return any type.
	 * @since 5.0.0
	 */
	public function current() {
		return $this->getNodes()[ $this->position ];
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

	/**
	 * @return bool
	 */
	public function valid() {
		return isset( $this->nodes[ $this->position ] );
	}

	/**
	 * @param $name
	 *
	 * @return Node|null
	 */
	public function getNodeByName( $name ): ?Node {
		foreach ( $this->getNodes() as $node ) {
			if ( $node->getName() == $name ) {
				return $node;
			}
		}

		return null;
	}

	/**
	 * @param string $tableName
	 * @param bool $useGeo
	 *
	 * @return Nodes
	 */
	public function getNodesWithTableName( string $tableName, bool $useGeo = true ): Nodes {
		$nodes = [];
		foreach ( $this->getNodes() as $node ) {
			if ( $node->containsTableName( $tableName ) ) {
				if ( ( $useGeo && ! is_null( $node->getGeo() ) ) && $node->getGeo() != ShardMatrix::getGeo() && ! is_null( ShardMatrix::getGeo() ) ) {
					continue;
				}
				$nodes[] = $node->setLastUsedTableName( $tableName );
			}
		}

		return new Nodes( $nodes );
	}

	/**
	 * @param string $tableName
	 * @param string $geo
	 *
	 * @return Nodes
	 */
	public function getNodesWithTableNameAndGeo( string $tableName, string $geo ): Nodes {
		$nodes = [];
		foreach ( $this->getNodes() as $node ) {
			if ( $node->containsTableName( $tableName ) && $node->getGeo() == $geo ) {

				$nodes[] = $node->setLastUsedTableName( $tableName );
			}
		}

		return new Nodes( $nodes );


	}


}