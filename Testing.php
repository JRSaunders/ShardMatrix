<?php

use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );

$node = ShardMatrix::getConfig()->getNodes()->getNodes()[0];

$table = new \ShardMatrix\Table( 'users');

$uuid = \ShardMatrix\Uuid::make( $node, $table);

echo $uuid->getNode()->getName();
echo $uuid->getTable()->getName();
echo $uuid;
