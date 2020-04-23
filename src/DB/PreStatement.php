<?php


namespace ShardMatrix\DB;


use ShardMatrix\DB\Interfaces\ShardDataRowInterface;
use ShardMatrix\Node;
use ShardMatrix\Uuid;

/**
 * Class PreStatement
 * @package ShardMatrix\DB
 */
class PreStatement {

	protected Node $node;
	protected string $sql;
	protected ?array $bind = null;
	protected ?Uuid $uuid = null;
	protected ?ShardDataRowInterface $dataRow = null;
	protected ?string $calledMethod = null;

	/**
	 * PreStatement constructor.
	 *
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 * @param Uuid|null $uuid
	 * @param ShardDataRowInterface|null $dataRow
	 * @param string|null $calledMethod
	 */
	public function __construct( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null, ?ShardDataRowInterface $dataRow = null, ?string $calledMethod = null ) {
		$this->node         = $node;
		$this->sql          = $sql;
		$this->bind         = $bind;
		$this->uuid         = $uuid;
		$this->dataRow      = $dataRow;
		$this->calledMethod = $calledMethod;
	}

	/**
	 * @return Node|string
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * @return string
	 */
	public function getSql(): string {
		return $this->sql;
	}

	/**
	 * @return array|null
	 */
	public function getBind(): ?array {
		return $this->bind;
	}

	/**
	 * @return ShardDataRowInterface|null
	 */
	public function getDataRow(): ?ShardDataRowInterface {
		return $this->dataRow;
	}

	/**
	 * @param ShardDataRowInterface|null $dataRow
	 *
	 * @return $this
	 */
	public function setDataRow( ?ShardDataRowInterface $dataRow ): PreStatement {
		$this->dataRow = $dataRow;

		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getCalledMethod(): ?string {
		return $this->calledMethod;
	}

	/**
	 * @param string|null $calledMethod
	 */
	public function setCalledMethod( ?string $calledMethod ): PreStatement {
		$this->calledMethod = $calledMethod;

		return $this;
	}

	/**
	 * @param Uuid|null $uuid
	 *
	 * @return PreStatement
	 */
	public function setUuid( ?Uuid $uuid ): PreStatement {
		$this->uuid = $uuid;

		return $this;
}

	/**
	 * @return Uuid|null
	 */
	public function getUuid(): ?Uuid {
		return $this->uuid;
	}
}