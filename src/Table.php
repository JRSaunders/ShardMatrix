<?php


namespace ShardMatrix;


class Table {
	protected ?string $hash = null;
	protected string $table;

	public function __construct( string $table ) {
		$this->table = $table;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->table;
	}

	/**
	 * @return string
	 */
	public function getHash(): string {
		return $this->hash ?? ( $this->hash = hash( ShardMatrix::TABLE_ALGO, $this->table ) );
	}

	/**
	 * @return string[]
	 */
	public function getUniqueColumns(): array {
		return ShardMatrix::getConfig()->getUniqueColumns()->getUniqueColumnByTableName( $this->getName() );
	}


}