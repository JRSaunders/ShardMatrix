<?php

namespace ShardMatrix;

use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid as Ruuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Class Uuid
 * @package ShardMatrix
 */
class Uuid implements \JsonSerializable {
	protected ?string $uuid = null;
	protected Table $table;
	protected Node $node;
	protected ?UuidInterface $ramseyUuid = null;

	/**
	 * Uuid constructor.
	 *
	 * @param string|null $uuid
	 */
	public function __construct( ?string $uuid = null ) {
		$this->uuid = $uuid;
	}

	/**
	 * @param Node $node
	 * @param Table $table
	 *
	 * @return Uuid
	 * @throws Exception
	 */
	public static function make( Node $node, Table $table ): Uuid {

		if ( ! $node->containsTableName( $table->getName() ) ) {
			throw new Exception( 'Table is not in the table groups for this Node!' );
		}

		$ramseyUuid = Ruuid::uuid6( new Hexadecimal( bin2hex( $node->getName() ) ) );

		return new static( $table->getHash() . '-' . $ramseyUuid->toString() );
	}

	/**
	 * @return string
	 */
	protected function stripToRamseyUuidString(): string {
		$parts = $this->getParts();
		array_shift( $parts );

		return join( '-', $parts );
	}

	/**
	 * @return array
	 */
	protected function getParts(): array {
		return explode( '-', $this->uuid );
	}

	/**
	 * @return string
	 */
	protected function getTableHash(): string {
		return $this->getParts()[0];
	}

	/**
	 * @return UuidInterface|null
	 */
	protected function getRamseyUuid(): ?UuidInterface {
		if ( is_string( $this->uuid ) ) {
			return $this->ramseyUuid ?? ( $this->ramseyUuid = Ruuid::fromString( $this->stripToRamseyUuidString() ) );
		}

		return null;
	}

	/**
	 * @return Node|null
	 */
	public function getNode(): ?Node {

		$node = $this->node ??
		        (
		        $this->node = ShardMatrix::getConfig()->getNodes()->getNodeByName(
			        hex2bin( $this->getRamseyUuid()->getFields()->getNode() )
		        )
		        );

		if ( $node ) {
			$node->setLastUsedTableName( $this->getTable()->getName() );
		}

		return $node;
	}

	/**
	 * @return Table|null
	 */
	public function getTable(): ?Table {
		return $this->table ??
		       (
		       $this->table = ShardMatrix::getConfig()->getTableGroups()->getTableByHash(
			       $this->getTableHash()
		       )
		       );
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		return $this->uuid ?? '';
	}

	/**
	 * @return bool
	 */
	public function isValid(): bool {

		if ( $this->getTable() && $this->getNode() && count( $this->getParts() ) == 6 ) {
			return true;
		}

		return false;
	}

	/**
	 * @return mixed|string|null
	 */
	public function jsonSerialize() {
		return $this->uuid;
	}
}