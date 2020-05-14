<?php


namespace ShardMatrix\DB\Builder;


use ShardMatrix\DB\Connections;
use ShardMatrix\Node;
use ShardMatrix\NodeDistributor;
use ShardMatrix\ShardMatrix;

class UnassignedNode extends Node {
	public function __construct() {
		parent::__construct( '___UNASSIGNED___', [] );

	}
}