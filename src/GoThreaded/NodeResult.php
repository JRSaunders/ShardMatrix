<?php


namespace ShardMatrix\GoThreaded;


class NodeResult {
	
	protected ?\stdClass $nodeResult = null;

	public function __construct( ?\stdClass $nodeResult = null ) {
		$this->nodeResult = $nodeResult;
	}

	public function getNodeName(): ?string {
		return $this->nodeResult->node_name ?? null;

	}

	public function getData(): array {
		return $this->nodeResult->data ?? [];
	}

	/**
	 * @return string|null
	 */
	public function getError(): ?string {
		if ( strlen( $this->nodeResult->error ?? '' ) ) {
			return $this->nodeResult->error;
		}

		return null;
	}
}