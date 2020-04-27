<?php


namespace ShardMatrix\Db\Builder;


use ShardMatrix\DB\Connections;

class UnassignedConnection extends ShardMatrixConnection {
	public function __construct() {
		$this->node        = new UnassignedNode();
		$this->reconnector = function () {
			$this->reconnector = function () {
				if ( $pdo = Connections::getLastUsedConnection() ) {
					$this->pdo  = $pdo;
					$this->node = Connections::getLastUsedNode();
				}
			};
		};
	}
}