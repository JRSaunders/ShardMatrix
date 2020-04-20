<?php


namespace ShardMatrix\DB;


use ShardMatrix\Uuid;

class Query {
	protected array $selects = [];

	protected array $wheres = [];

	public function getTableName(): string {
		return '';
	}


	public function getSql(): string {
		return '';
	}

	public function getLastUuid(): Uuid {
		return $this->lastUuid;
	}

	public function getBinds(): ?array {
		return null;
	}

	public function limit(): int {
		return 10;
	}

	/**
	 * @param array $wheres
	 *
	 * @return $this
	 */
	public function setWheres( array $wheres ): Query {
		$this->wheres = $wheres;

		return $this;
	}

	public function where( Where $where ) {
		$this->wheres[] = $where;
	}


}