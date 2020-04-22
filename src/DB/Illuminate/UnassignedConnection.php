<?php


namespace ShardMatrix\Db\Illuminate;


class UnassignedConnection extends ShardMatrixConnection {
	public function __construct() {
		$this->node = new UnassignedNode();
	}
}