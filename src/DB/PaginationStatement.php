<?php


namespace ShardMatrix\DB;


use ShardMatrix\DB\Interfaces\ResultsInterface;
use ShardMatrix\Uuid;

/**
 * Class PaginationStatement
 * @package ShardMatrix\DB
 */
class PaginationStatement {

	protected int $currentPageNumber = 1;
	protected int $resultsPerPage = 15;
	protected array $markerData = [];
	protected ?ResultsInterface $results = null;

	/**
	 * PaginationStatement constructor.
	 *
	 * @param array $markerData
	 * @param ResultsInterface $results
	 * @param int $currentPageNumber
	 * @param int $resultsPerPage
	 */
	public function __construct( array $markerData, int $currentPageNumber = 1, $resultsPerPage = 15 ) {
		$this->markerData        = $markerData;
		$this->currentPageNumber = $currentPageNumber;
		$this->resultsPerPage    = $resultsPerPage;
	}

	/**
	 * @param $pageNumber
	 *
	 * @return array
	 */
	public function getUuidsFromPageNumber( $pageNumber ): array {
		$returnUuids = [];
		$initIndex   = ( $pageNumber - 1 ) * $this->resultsPerPage;
		if ( isset( $this->markerData[ $initIndex ] ) ) {
			for ( $i = 0; $i < $this->resultsPerPage; $i ++ ) {
				if ( isset( $this->markerData[ $initIndex + $i ] ) ) {
					$returnUuids[] = $this->markerData[ $initIndex + $i ];
				} else {
					break;
				}
			}
		}

		return $returnUuids;

	}

	public function countResults(): int {
		return count( $this->markerData );
	}

	public function countPages(): int {
		return ceil( count( $this->markerData ) / $this->resultsPerPage );
	}

	/**
	 * @return int
	 */
	public function getCurrentPageNumber(): int {
		return $this->currentPageNumber;
	}

	/**
	 * @return ResultsInterface|null
	 */
	public function getResults(): ResultsInterface {
		return $this->results ?? new ShardMatrixStatement( null, null, null );
	}

	/**
	 * @param ResultsInterface|null $results
	 *
	 * @return PaginationStatement
	 */
	public function setResults( ?ResultsInterface $results ): PaginationStatement {
		$this->results = $results;

		return $this;
	}
}