<?php


namespace ShardMatrix\DB;


use ShardMatrix\DB\Builder\QueryBuilder;
use ShardMatrix\Node;

class NodeQueryModifiers {
	/**
	 * @var NodeQueryModifier[]
	 */
	protected array $nodeQueryModifiers = [];

	/**
	 * NodeQueryModifiers constructor.
	 *
	 * @param array $nodeQueryModifiers
	 */
	public function __construct( array $nodeQueryModifiers = [] ) {
		$this->setNodeQueryModifiers( $nodeQueryModifiers );
	}

	/**
	 * @param array $nodeQueryModifiers
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function setNodeQueryModifiers( array $nodeQueryModifiers ): NodeQueryModifiers {

		foreach ( $nodeQueryModifiers as $modifier ) {
			if ( ! $modifier instanceof NodeQueryModifier ) {
				throw new Exception( 'Modifiers need to be ' . NodeQueryModifier::class );
			}
		}

		$this->nodeQueryModifiers = $nodeQueryModifiers;

		return $this;
	}

	/**
	 * @param Node $node
	 * @param QueryBuilder $query
	 *
	 * @return QueryBuilder
	 */
	public function modifyQueryForNode( Node $node, QueryBuilder $query ): ?QueryBuilder {

		foreach ( $this->nodeQueryModifiers as $modifier ) {
			if ( $node->getName() == $modifier->getNode()->getName() ) {
				return $modifier->getModifierQuery();
			}
		}

		return null;
	}
}