<?php

namespace ShardMatrix;

use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid as Ruuid;

class Uuid {
	protected ?string $uuid = null;
	protected Table $table;

	public function __construct( ?string $uuid = null ) {


	}

	public function create() {

		$node = new Hexadecimal( bin2hex( "DB0003" ) );
		$uuid = Ruuid::uuid6( $node );

		var_dump(
			$this->table->getHash(),
			hex2bin( $uuid->getNodeHex() ),
			$uuid->toString(),
			$uuid->getFields()->getVersion()
		);


	}

	/**
	 * @return Table|null
	 */
	public function getTable(): Table {
		return $this->table;
	}

	/**
	 * @param Table|null $table
	 */
	public function setTable( string $table ): Uuid {
		$this->table = new Table( $table );
		return $this;
	}

}