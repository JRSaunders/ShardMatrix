<?php


namespace ShardMatrix\DB;


use ShardMatrix\Exception;
use ShardMatrix\Node;
use ShardMatrix\ShardMatrix;

class Connections {

	protected static $connections = [];

	public static function getNodeConnection( Node $node ): \PDO {
		if ( isset( static::$connections[ $node->getName() ] ) ) {
			return static::$connections[ $node->getName() ];
		}
		$db = new \PDO( $node->getDsn(), $node->getUsername(), $node->getPassword() );
		return static::$connections[ $node->getName() ] = $db;

	}

	/**
	 * @param string $nodeName
	 *
	 * @return \PDO
	 * @throws Exception
	 */
	public function getConnectionByNodeName( string $nodeName ) {
		$node = ShardMatrix::getConfig()->getNodes()->getNodeByName( $nodeName );
		if ( $node ) {
			return static::getNodeConnection( $node );
		}
		throw new Exception( 'No Node by name ' . $nodeName . ' Exists!' );
	}
}