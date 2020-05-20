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
	protected bool $freshDataOnly = false;

	/**
	 * PreStatement constructor.
	 *
	 * @param Node $node
	 * @param string $sql
	 * @param array|null $bind
	 * @param Uuid|null $uuid
	 * @param ShardDataRowInterface|null $dataRow
	 * @param string|null $calledMethod
	 * @param bool $freshDataOnly
	 */
	public function __construct( Node $node, string $sql, ?array $bind = null, ?Uuid $uuid = null, ?ShardDataRowInterface $dataRow = null, ?string $calledMethod = null, $freshDataOnly = false ) {
		$this->node          = $node;
		$this->sql           = $sql;
		$this->bind          = $bind;
		$this->uuid          = $uuid;
		$this->dataRow       = $dataRow;
		$this->calledMethod  = $calledMethod;
		$this->freshDataOnly = $freshDataOnly;
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

	public function getHashKey(): string {
		$hashParts = [];
		if ( $this->getUuid() ) {
			$hashParts[] = $this->getUuid();
		} else {
			$hashParts[] = 'ALL';
		}

		$hashParts[] = md5( str_replace( [
			'"',
			'`',
			"'",
			"$",
			"?",
			" "
		], '', strtolower( $this->getSql() . ( join( '-', $this->getBind() ) ) ) ) );

		return join( ':', $hashParts );
	}

	/**
	 * @param bool $freshDataOnly
	 *
	 * @return PreStatement
	 */
	public function setFreshDataOnly( bool $freshDataOnly ): PreStatement {
		$this->freshDataOnly = $freshDataOnly;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isFreshDataOnly(): bool {
		return $this->freshDataOnly;
	}
}