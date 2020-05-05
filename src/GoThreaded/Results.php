<?php

namespace ShardMatrix\GoThreaded;

use ShardMatrix\Nodes;

class Results {
	protected ?\stdClass $results;

	/**
	 * Results constructor.
	 *
	 * @param \stdClass|null $results
	 */
	public function __construct( ?\stdClass $results = null ) {
		$this->results = $results;
	}

	/**
	 * @return NodeResult[]
	 */
	public function getNodes(): array {
		if ( isset( $this->results->nodes ) ) {
			foreach ( $this->results->nodes as &$result ) {
				if ( ! $result instanceof NodeResult ) {
					$result = new NodeResult( $result );
				}
			}

			return $this->results->nodes;
		}

		return [];
	}
}