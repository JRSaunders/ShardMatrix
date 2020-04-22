<?php


namespace ShardMatrix\Db\Illuminate;


use ShardMatrix\Node;

class UnassignedNode extends Node {
	public function __construct() {
		parent::__construct( '___UNASSIGNED___', [] );
	}
}