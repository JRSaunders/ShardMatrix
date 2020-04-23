<?php


namespace ShardMatrix\DB\Interfaces;


use ShardMatrix\DB\Exception;
use ShardMatrix\Uuid;

interface DBDataRowTransactionsInterface extends ShardDataRowInterface {
	/**
	 * @return Uuid|null
	 * @throws Exception
	 * @throws \ShardMatrix\Exception
	 */
	public function create():?Uuid;

	/**
	 * @param array $options
	 *
	 * @return mixed
	 */
	public function save(array $options = []);

	/**
	 * @return bool
	 */
	public function delete();

}