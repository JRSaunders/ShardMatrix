<?php


namespace ShardMatrix\DB;

/**
 * Class NodeQueries
 * @package ShardMatrix\DB
 */
class NodeQueries implements \Iterator, \JsonSerializable {

	protected $position = 0;
	/**
	 * @var NodeQuery[]
	 */
	protected $nodeQueries = [];

	/**
	 * NodeQueries constructor.
	 *
	 * @param array $nodeQueries
	 */
	public function __construct( array $nodeQueries ) {
		$this->nodeQueries = $nodeQueries;
	}

	/**
	 * @return array|NodeQuery[]
	 */
	public function getNodeQueries(): array {
		return $this->nodeQueries;
	}

	/**
	 * @return NodeQuery
	 */
	public function current() {
		return $this->getNodeQueries()[ $this->position ];
	}

	public function next() {
		$this->position ++;
	}

	public function key() {
		return $this->position;
	}

	public function valid() {
		return isset( $this->nodeQueries[ $this->position ] );
	}

	public function rewind() {
		$this->position = 0;
	}

	public function jsonSerialize() {
		return $this->nodeQueries;
	}
}