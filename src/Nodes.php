<?php


namespace ShardMatrix;


/**
 * Class Nodes
 * @package ShardMatrix
 */
class Nodes implements \Iterator {

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


	public function countNodes(): int {
		return count( $this->getNodes() );
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

	public function getNodesWithTableName( string $tableName): Nodes {
		$nodes = [];
		foreach ( $this->getNodes() as $node ) {
			if ( $node->containsTableName( $tableName ) ) {
				$nodes[] = $node;
			}
		}

		return new Nodes( $nodes );
	}


}