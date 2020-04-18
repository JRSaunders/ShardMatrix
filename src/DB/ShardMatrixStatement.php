<?php


namespace ShardMatrix\DB;


use ShardMatrix\Node;
use ShardMatrix\Uuid;

class ShardMatrixStatement {
	protected ?\PDOStatement $pdoStatement = null;
	protected ?Node $node = null;
	protected ?Uuid $uuid = null;
	protected array $data = [];

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

	public function fetchAllArrays(): array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetchAll( \PDO::FETCH_ASSOC );
			}
		}
		if ( $this->data ) {
			return $this->data;
		}

		return [];
	}

	public function fetchAllObjects(): array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetchAll( \PDO::FETCH_OBJ );
			}
		}
		if ( $this->data ) {
			$returnArray = [];
			foreach ( $this->data as $data ) {
				$returnArray[] = (object) $data;
			}

			return $returnArray;
		}

		return [];
	}

	public function fetchRowArray(): ?array {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetch( \PDO::FETCH_ASSOC );
			}
		}
		if ( $this->data && isset( $this->data[0] ) ) {
			return $this->data[0];
		}

		return null;
	}

	public function fetchRowObject(): ?\stdClass {
		if ( $this->pdoStatement ) {
			if ( $this->pdoStatement->rowCount() > 0 ) {
				return $this->pdoStatement->fetch( \PDO::FETCH_OBJ );
			}
		}
		if ( $this->data && isset( $this->data[0] ) ) {
			return (object) $this->data[0];
		}

		return null;
	}

	public function __preSerialize() {
		$this->data         = $this->fetchAllArrays();
		$this->pdoStatement = null;
	}

}