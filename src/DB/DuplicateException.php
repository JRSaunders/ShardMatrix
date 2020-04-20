<?php


namespace ShardMatrix\DB;

use Throwable;

class DuplicateException extends Exception {

	protected array $duplicateColumns = [];

	public function __construct( array $duplicateColumns, $message = "", $code = 0, Throwable $previous = null ) {
		$this->duplicateColumns = $duplicateColumns;
		parent::__construct( $message, $code, $previous );
	}

	/**
	 * @return array
	 */
	public function getDuplicateColumns(): array {
		return $this->duplicateColumns;
	}
}