<?php

use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );


//(new \ShardMatrix\Uuid())->setTable( 'user')->create();
var_dump(
	ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0006')->getTableGroups()->getTableGroupByTableName( 'visitors')->getTables()
);