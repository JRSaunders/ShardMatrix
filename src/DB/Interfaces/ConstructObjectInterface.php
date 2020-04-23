<?php


namespace ShardMatrix\DB\Interfaces;


interface ConstructObjectInterface {
	public function __construct(\stdClass $object);
}