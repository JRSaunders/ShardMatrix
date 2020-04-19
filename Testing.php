<?php

use ShardMatrix\DB\ShardDB;
use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );
ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );
//$f = ( new ShardQuery() )->allNodeQuery( 'users', "CREATE TABLE users (
//    uuid VARCHAR(50) NOT NULL PRIMARY KEY,
//    username VARCHAR(100),
//    password VARCHAR(100),
//    email VARCHAR(200)
//) ENGINE=InnoDB;" );
//$f = ( new ShardQuery() )->allNodeQuery( 'users', "select * from users" ,null,'username','asc');
//var_dump($f->fetchRowArray());
$username = 'tim' . rand( 500, 1000 );
$password = 'pass' . rand( 500, 1000 );
$email    = 'email' . rand( 500, 1000 ) . '@google.com';
$shardDb  = new ShardDB();
$shardDb->setCheckSuccessFunction( function ( \ShardMatrix\DB\ShardMatrixStatement $statement, string $calledMethod ) use ( $shardDb ) {
	if ( $calledMethod == 'insert' && $statement->getUuid()->getTable()->getName() == 'users' ) {
		$email = $shardDb->getByUuid( $statement->getUuid() )->email;
		$checkDupes = $shardDb->nodesQuery( $statement->getAllTableNodes(), "select uuid from users where email = :email and uuid != :uuid", [ ':email' => $email ,':uuid' => $statement->getUuid()->toString()] );
		if($checkDupes->isSuccessful()){
			$shardDb->deleteByUuid( $statement->getUuid());
			throw new \Exception('Duplicate Record');
		}
	}

	return true;
} );
$shardDb->setDefaultRowReturnClass( \ShardMatrix\DB\TestRow::class);
$x = $shardDb->allNodesQuery( 'users', 'select * from users');
foreach($x->fetchResultSet() as $row){
	echo $row->getTest();
}
//$shardDb->insert( 'users', "insert into users  (uuid,username,password,email) values (:uuid,:username,:password,:email);", [
//	':username' => $username,
//	':password' => $password,
//	':email'    => $email
//] );

//ShardMatrix::getConfig()->getUniqueColumns();



//$stmt = ( new ShardQuery() )->test( ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0001' ), 'select * from users' );
//var_dump( $stmt );

//(new ShardQuery())->allNodeQuery( 'users', "select * from users where username = :username",[':username'=>'bobbyB45'])->fetchRowObject();

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

//echo \ShardMatrix\NodeDistributor::getNode( 'visitors')->getName().PHP_EOL;
//echo \ShardMatrix\NodeDistributor::getNode( 'sign_ups')->getName().PHP_EOL;
//echo \ShardMatrix\NodeDistributor::getNode( 'users')->getName().PHP_EOL;
//echo \ShardMatrix\NodeDistributor::getNode( 'payments')->getName().PHP_EOL;
//echo 'parent '.uniqid(getmypid().'-').PHP_EOL;
//$results = [];
//for($i=0;$i<10;$i++) {
//	$pid = pcntl_fork();
//	if ( $pid == - 1 ) {
//		die( 'could not fork' );
//	} else if ( $pid ) {
//		// we are the parent
//		pcntl_wait( $status ); //Protect against Zombie children
//		var_dump( $status );
//	} else {
//		echo 'I am a child '.$i.' ' . getmypid() . PHP_EOL;
//		$results[] = 'child';
//		exit;
//	}
//}
//echo 'back in parent '.getmypid().PHP_EOL;
//var_dump($results);

