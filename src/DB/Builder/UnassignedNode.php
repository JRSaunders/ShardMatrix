<?php


namespace ShardMatrix\Db\Builder;


use ShardMatrix\Node;

class UnassignedNode extends Node {
	public function __construct() {
		parent::__construct( '___UNASSIGNED___', [] );
	}
}