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
		if ( $remove && $preStatement->getUuid() ) {
			$shardDb->getPdoCache()->scanAndClean( $preStatement->getUuid() . ':' );
			$shardDb->getPdoCache()->clean( $preStatement->getUuid() );
		} elseif ( ! $preStatement->isFreshDataOnly() && ! $useNewConnection ) {
			$cacheRead = $shardDb->getPdoCache()->read( $key );
			if ( $cacheRead instanceof ResultsInterface && $cacheRead->isSuccessful() ) {
				return $cacheRead;
			}
		}
		$returnValue = $shardDb->__execute( $preStatement, $useNewConnection, $rollbacks );
		if ( $returnValue instanceof ResultsInterface && $preStatement->isSelectQuery() ) {
			$shardDb->getPdoCache()->write( $key, $returnValue );
		}

		return $returnValue;

	}


}