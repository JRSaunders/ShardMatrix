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
	 * @param int $pageNumber
	 *
	 * @return Uuid|null
	 */
	public function getPageNumberUuid( int $pageNumber ): ?Uuid {
		if ( isset( $this->markerData[ ( $pageNumber - 1 ) ] ) ) {
			return new Uuid( $this->markerData[ ( $pageNumber - 1 ) ]->uuid );
		}

		return null;
	}

	public function countPages(): int {
		return count( $this->markerData );
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