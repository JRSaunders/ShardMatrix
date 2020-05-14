<?php


namespace ShardMatrix\DB\Builder;


use ShardMatrix\DB\Exception;
use ShardMatrix\Node;
use Throwable;

/**
 * Class BuilderException
 * @package ShardMatrix\DB\Builder
 */
class BuilderException extends Exception {
	/**
	 * @var Node|null
	 */
	protected ?Node $node = null;

	/**
	 * BuilderException constructor.
	 *
	 * @param Node|null $node
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct( ?Node $node, $message = "", $code = 0, Throwable $previous = null ) {
		$this->node = $node;
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return Node|null
	 */
	public function getNode(): ?Node {
		return $this->node;
	}
}