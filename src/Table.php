<?php


namespace ShardMatrix;


class Table {
	protected ?string $hash = null;
	protected string $table;

	public function __construct( string $table ) {
		$this->table = $table;
	}

	public function getName(): string {
		return $this->table;
	}

	public function getHash() {
		return $this->hash ?? ( $this->hash = hash( ShardMatrix::TABLE_ALGO, $this->table ) );
	}


}