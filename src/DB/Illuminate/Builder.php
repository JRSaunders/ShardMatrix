<?php

namespace ShardMatrix\Db\Illuminate;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;

class Builder extends \Illuminate\Database\Query\Builder {

	public function __construct( ConnectionInterface $connection, Grammar $grammar = null, Processor $processor = null ) {
		parent::__construct( $connection, $grammar, $processor );
	}

}