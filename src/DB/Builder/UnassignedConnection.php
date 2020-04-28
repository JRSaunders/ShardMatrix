<?php


namespace ShardMatrix\Db\Builder;


use ShardMatrix\DB\Connections;

/**
 * Class UnassignedConnection
 * @package ShardMatrix\Db\Builder
 */
class UnassignedConnection extends ShardMatrixConnection {
	/**
	 * UnassignedConnection constructor.
	 */
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