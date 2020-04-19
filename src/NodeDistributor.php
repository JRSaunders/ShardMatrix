<?php


namespace ShardMatrix;


class NodeDistributor {

	protected static $groupNodes = [];

	static public function setFromUuid( Uuid $uuid ) {
		static::$groupNodes[ $uuid->getNode()
		                          ->getTableGroups()
		                          ->getTableGroupByTableName( $uuid->getTable()->getName() )
		                          ->getName() ] = $uuid->getNode();
	}

	/**
	 * @param string $tableName
	 * @param bool $forNewUuid
	 *
	 * @return Node
	 * @throws Exception
	 */
	static public function getNode( string $tableName ): Node {
		$group = ShardMatrix::getConfig()->getTableGroups()->getTableGroupByTableName( $tableName );
		if ( isset( static::$groupNodes[ $group->getName() ] ) ) {
			return static::$groupNodes[ $group->getName() ]->setLastUsedTableName( $tableName );
		}

		$nodes     = ShardMatrix::getConfig()->getNodes()->getNodesWithTableName( $tableName );
		$count     = $nodes->countNodes();
		$randomKey = rand( 1, $count ) - 1;

		$node = $nodes->getInsertNodes()[ $randomKey ];
		if ( $node && $group ) {
			static::$groupNodes[ $group->getName() ] = $node;

			return $node->setLastUsedTableName( $tableName );
		}

		throw new Exception( 'No Node Found for ' . $tableName, 1 );
	}

	static public function clearGroupNodes() {
		static::$groupNodes = [];
	}

}