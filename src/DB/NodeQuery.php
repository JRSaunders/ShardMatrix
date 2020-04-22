<?php


namespace ShardMatrix\DB;


use ShardMatrix\Node;

/**
 * Class NodeQuery
 * @package ShardMatrix\DB
 */
class NodeQuery {
	protected Node $node;
	protected string $sql;
	protected ?array $binds;

	/**
	 * NodeQuery constructor.
	 *
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $binds
	 */
	public function __construct( Node $node, string $sql, ?array $binds ) {
		$this->node  = $node;
		$this->sql   = $sql;
		$this->binds = $binds;
	}

	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @return string
	 */
	public function getSql(): string {
		return $this->sql;
	}

	public function getBinds(): ?array {
		return $this->binds;
	}

}