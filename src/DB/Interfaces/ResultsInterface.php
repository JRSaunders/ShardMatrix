<?php


namespace ShardMatrix\DB\Interfaces;


use ShardMatrix\DB\DataRow;
use ShardMatrix\DB\DataRows;
use ShardMatrix\DB\GroupSums;
use ShardMatrix\Uuid;

interface ResultsInterface {


	/**
	 * @return array
	 */
	public function fetchAllArrays(): array;

	/**
	 * @return array
	 */
	public function fetchAllObjects(): array;

	/**
	 * @return array
	 */
	public function fetchRowArray(): array;

	/**
	 * @return \stdClass|null
	 */
	public function fetchRowObject(): ?\stdClass;


	/**
	 * @return DataRows
	 */
	public function fetchDataRows(): DataRows;

	/**
	 * @return DataRow|null
	 */
	public function fetchDataRow(): ?ShardDataRowInterface;

	/**
	 * @return bool
	 */
	public function isSuccessful(): bool;

	/**
	 * @return Uuid|null
	 */
	public function getLastInsertUuid(): ?Uuid;

	/**
	 * @return int
	 */
	public function rowCount(): int;


	/**
	 * @param string $column
	 *
	 * @return float
	 */
	public function sumColumn( string $column ): float;

	/**
	 * @param string $column
	 *
	 * @return float
	 */
	public function avgColumn( string $column ): float;

	/**
	 * @param string $column
	 *
	 * @return float
	 */
	public function minColumn( string $column ): ?float;

	/**
	 * @param string $column
	 *
	 * @return float
	 */
	public function maxColumn( string $column ): ?float;

	/**
	 * @param string $column
	 * @param string $groupByColumn
	 *
	 * @return GroupSums
	 */
	public function sumColumnByGroup( string $column, string $groupByColumn ): GroupSums;

	/**
	 * @return bool
	 */
	public function isFromCache(): bool;

	/**
	 * @param bool $fromCache
	 */
	public function setFromCache( bool $fromCache = true ): void;

}