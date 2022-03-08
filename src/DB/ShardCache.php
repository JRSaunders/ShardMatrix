<?php


namespace ShardMatrix\DB;


use ShardMatrix\DB\Interfaces\ResultsInterface;

/**
 * Class ShardCache
 * @package ShardMatrix\DB
 */
class ShardCache {

	/**
	 * @param PreStatement $preStatement
	 * @param bool $useNewConnection
	 * @param bool $rollbacks
	 * @param ShardDB $shardDb
	 *
	 * @return mixed|ResultsInterface|ShardMatrixStatement|null
	 * @throws DuplicateException
	 * @throws \ShardMatrix\Exception
	 */
	public static function execute(
		PreStatement $preStatement, bool $useNewConnection = false,
		bool $rollbacks = false, ShardDB $shardDb
	) {
		$method = $preStatement->getCalledMethod() ? strtolower( $preStatement->getCalledMethod() ) : '';
		$remove = false;
		if ( $preStatement->isUpdateQuery() || $preStatement->isDeleteQuery() ) {
			$remove = true;
		}
		if ( strpos( $method, 'insert' ) !== false ) {
			return $shardDb->__execute( $preStatement, $useNewConnection, $rollbacks );
		}
		$key = $preStatement->getHashKey();
		if ( $remove ) {
			if ( $preStatement->getUuid() ) {
				$shardDb->getPdoCache()->clean( $preStatement->getUuid() );
				if ( $preStatement->isFreshDataOnly() ) {
					$shardDb->getPdoCache()->cleanAllMatching( $preStatement->getUuid() );
				}
			} else {
				$shardDb->getPdoCache()->clean( $key );
			}
		} elseif ( ! $preStatement->isFreshDataOnly() && ! $useNewConnection ) {
			if ( $preStatement->getUuid() ) {
				$cacheRead = $shardDb->getPdoCache()->read( $preStatement->getUuid() );
				if ( $cacheRead instanceof ResultsInterface && $cacheRead->isSuccessful() && $cacheRead->rowCount() == 1 ) {
					return $cacheRead;
				}
			} else {
				$cacheRead = $shardDb->getPdoCache()->read( $key );
				if ( $cacheRead instanceof ResultsInterface && $cacheRead->isSuccessful() ) {
					return $cacheRead;
				}
			}
		}
		$returnValue = $shardDb->__execute( $preStatement, $useNewConnection, $rollbacks );
		if ( $returnValue instanceof ResultsInterface && $preStatement->isSelectQuery() && ! $preStatement->getUuid() ) {
			$shardDb->getPdoCache()->write( $key, $returnValue );
		} else if ( $returnValue instanceof ResultsInterface && $preStatement->isSelectQuery() && $preStatement->getUuid() && $returnValue->rowCount() == 1 ) {
			$shardDb->getPdoCache()->write( $preStatement->getUuid(), $returnValue );
		}

		return $returnValue;

	}


}