<?php


namespace ShardMatrix\DB;


use ShardMatrix\Uuid;

class ShardMatrixStatement {
	protected ?\PDOStatement $pdoStatement = null;
	protected ?Node $node = null;
	protected ?Uuid $uuid = null;

	public function __construct( ?\PDOStatement $pdoStatement, ?Node $node, ?Uuid $uuid ) {
		$this->uuid         = $uuid;
		$this->node         = $node;
		$this->pdoStatement = $pdoStatement;
	}

	/**
	 * @return \PDOStatement|null
	 */
	public function getPdoStatement(): ?\PDOStatement {
		return $this->pdoStatement;
	}

	/**
	 * @return Node|null
	 */
	public function getNode(): ?Node {
		return $this->node;
	}

	/**
	 * @return Uuid|null
	 */
	public function getUuid(): ?Uuid {
		return $this->uuid;
	}

	public function fetchAllArrays(): ?array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetchAll( \PDO::FETCH_ASSOC );
			}
		}
	}

	public function fetchAllObjects(): ?array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetchAll( \PDO::FETCH_OBJ );
			}
		}

		return null;
	}

	public function fetchRowArray(): ?array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetch( \PDO::FETCH_ASSOC );
			}
		}

		return null;
	}

	public function fetchRowObject(): ?\stdClass {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetch( \PDO::FETCH_OBJ );
			}
		}

		return null;
	}
}