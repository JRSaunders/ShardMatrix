<?php

use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );

//$node = ShardMatrix::getConfig()->getNodes()->getNodes()[4];
////make NODE distributer
//$table = new \ShardMatrix\Table( 'visitors');
//
//$uuid = \ShardMatrix\Uuid::make( $node, $table);
//
//echo $uuid->getNode()->getName();
//echo $uuid->getTable()->getName();
//echo $uuid;

//$uuid = new \ShardMatrix\Uuid('0fca0384-1ea80cba-e8a2-6a8c-bacd-444230303035');
//
//echo $uuid->getTable()->getName().' '.$uuid->getNode()->getName();
//

echo \ShardMatrix\NodeDistributor::getNode( 'visitors')->getName().PHP_EOL;
echo \ShardMatrix\NodeDistributor::getNode( 'sign_ups')->getName().PHP_EOL;
echo \ShardMatrix\NodeDistributor::getNode( 'users')->getName().PHP_EOL;
echo \ShardMatrix\NodeDistributor::getNode( 'payments')->getName().PHP_EOL;
