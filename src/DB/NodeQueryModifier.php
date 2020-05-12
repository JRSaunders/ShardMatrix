<?php


namespace ShardMatrix\DB;


use ShardMatrix\Db\Builder\QueryBuilder;
use ShardMatrix\Node;

class NodeQueryModifier {
	protected Node $node;
	protected QueryBuilder $modifierQuery;

	public function __construct( Node $node, QueryBuilder $modifierQuery ) {
		$this->node          = $node;
		$this->modifierQuery = $modifierQuery;
	}

	/**
	 * @return Node
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * @return QueryBuilder
	 */
	public function getModifierQuery(): QueryBuilder {
		return $this->modifierQuery;
	}
}