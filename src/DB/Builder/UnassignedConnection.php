<?php


namespace ShardMatrix\Db\Builder;


class UnassignedConnection extends ShardMatrixConnection {
	public function __construct() {
		$this->node = new UnassignedNode();
	}
}