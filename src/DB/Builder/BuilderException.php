<?php


namespace ShardMatrix\Db\Builder;


use ShardMatrix\DB\Exception;
use Throwable;

/**
 * Class BuilderException
 * @package ShardMatrix\Db\Builder
 */
class BuilderException extends Exception {
	protected ?Node $node = null;
	public function __construct( ?Node $node, $message = "", $code = 0, Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return Node|null
	 */
	public function getNode(): ?Node {
		return $this->node;
	}
}