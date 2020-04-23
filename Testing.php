<?php

use ShardMatrix\Db\Builder\DB;
use ShardMatrix\Db\Builder\QueryBuilder;
use ShardMatrix\Db\Builder\ShardMatrixConnection;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\Dsn;
use ShardMatrix\NodeDistributor;
use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );
ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );
ShardMatrix::setGeo( 'UK' );
//$f = ( new ShardDB() )->allNodesQuery( 'users', "ALTER TABLE users add created DATETIME null; " );
//$f = ( new ShardDB() )->allNodesQuery( 'users', "select * from users" ,null,'username','asc');
//var_dump($f->fetchRowArray());

//$shardDb   = new ShardDB();
//$tableName = 'users';
//$con       = new ShardMatrixConnection(
//		NodeDistributor::getNode( $tableName )
//	);
//
//$q = $con->query()->select('username')->from( 'users')->whereBetween( 'created',['2020-04-19','2020-04-21'])->limit(10);


//
//echo $q->toSql();
//
//var_dump( $q->getBindings());
//
//$x = $shardDb->allNodesQuery( 'users', 'select * from `users` where `created` between ? and ? and uuid = ? limit 10', ['2020-04-19','2020-04-21','06a00233-1ea82fe3-46ef-6464-8494-444230303031']);
//
//echo $x->rowCount();
//

//$x = $shardDb->allNodesQuery( 'users', 'select * from users limit 10' );

//var_dump($x->fetchResultSet());
//var_dump( $shardDb->allNodesQuery( 'users', 'select count(*) as count ,
// HOUR( created ) as hours  from users group by hours' )->sumColumnByGroup( 'count', 'hours' ));
//$dsn = new Dsn('mysql:dbname=shard;host=localhost:3304;user=root;password=password');
//echo $dsn;
//$x = $shardDb->allNodesQuery( 'users', "select * from users where created between '2020-04-20 11:50:10' and   '2020-04-20 12:20:00' order by uuid desc limit 300;",null,'uuid','asc');

//$x = $shardDb->allNodesQuery( 'users', "select * from users where uuid > '06a00233-1ea830fb-874c-6cb4-ac3a-444230303033' order by uuid asc;",null,'uuid','asc');


//var_dump( $x->fetchAllObjects());
//
//$i = 0;
//while ( $i < 100000 ) {
//	$username = 'randy' . rand( 5000, 10000000 ) . uniqid();
//	$password = 'cool!!' . rand( 5000, 100000 );
//	$email    = 'timmy' . rand( 1, 10000000 ) . uniqid() . '@google.com';
//	$created  = ( new DateTime() )->format( 'Y-m-d H:i:s' );
//	$i ++;
//	try {
//		\ShardMatrix\DB\Connections::closeConnections();
//		$shardDb = new ShardDB();
//		$shardDb->newNodeInsert( 'users', "insert into users  (uuid,username,password,email,created) values (:uuid,:username,:password,:email,:created);", [
//			':username' => $username,
//			':password' => $password,
//			':email'    => $email,
//			':created'  => $created
//		] );
//	} catch ( \ShardMatrix\DB\Exception $exception ) {
//		echo $exception->getMessage() . PHP_EOL;
//	}
//}
//$shardDb->setCheckSuccessFunction( function ( \ShardMatrix\DB\ShardMatrixStatement $statement, string $calledMethod ) use ( $shardDb ) {
//	if ( $calledMethod == 'insert' && $statement->getUuid()->getTable()->getName() == 'users' ) {
//		$email = $shardDb->getByUuid( $statement->getUuid() )->email;
//		$checkDupes = $shardDb->nodesQuery( $statement->getAllTableNodes(), "select uuid from users where email = :email and uuid != :uuid", [ ':email' => $email ,':uuid' => $statement->getUuid()->toString()] );
//		if($checkDupes->isSuccessful()){
//			$shardDb->deleteByUuid( $statement->getUuid());
//			throw new \Exception('Duplicate Record');
//		}
//	}
//
//	return true;
//} );
//$shardDb->setDefaultRowReturnClass( \ShardMatrix\DB\TestRow::class);


//$shardDb->insert( 'users', "insert into users (uuid,email,username,password) values (:uuid,'email50ss5@google.com','odeq234iwuow','qwug234ddugwq');");
//
//$x = $shardDb->allNodesQuery( 'users', 'select * from users limit 23000;' );
//$i = 0;
//foreach($x->fetchResultSet() as $row){
//	echo $row->getUuid()->__toString().PHP_EOL.$i++;
//}
//echo PHP_EOL.$x->rowCount();


//
//foreach ($x->getShardMatrixStatements() as $s){
//	echo PHP_EOL.$s->getQueryString().PHP_EOL;
//	echo PHP_EOL.$s->getNode()->getName().':'.$s->rowCount().PHP_EOL;
//	//var_dump($s->fetchResultSet()->jsonSerialize());
//}

//$shardDb->deleteByUuid( new \ShardMatrix\Uuid('06a00233-1ea82566-fa3d-6066-ac4d-444230303031'));

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

//$q = new QueryBuilder();
//
//$con = new ShardMatrixConnection( NodeDistributor::getNode( 'users' ) );
//$con->prepareQuery( $q );

//$x = DB::table( 'users')->where('uuid' ,'>','06a00233-1ea82fe3-79a2-6b72-98eb-444230303033' )->limit(40)->getBindings();
//
//var_dump($x);

//DB::table( 'users')->whereUuid( '06a00233-1ea82fe3-79a2-6b72-98eb-444230303033')->get()->each(function(\ShardMatrix\Db\Illuminate\Model $model){
//	$model->username = 'harry';
//	var_dump($model->save() );
//});

//$user = DB::getByUuid( '06a00233-1ea82fe3-46ef-6464-8494-444230303031');
//$user->username = 'tim48135';
//var_dump($user->__toArray());
////var_dump($user->save());
////
////var_dump(DB::getByUuid( '06a00233-1ea82fe3-79a2-6b72-98eb-444230303033'));


$sdb = new ShardDB();

$x = $sdb->insert( 'users', "insert into users ( uuid, username, email , password , created ) values (:uuid , :username, :email, :password, :created )", [
	':username' => 'tim4813511',
	':email'    => 'john@poo11.com',
	':password' => 'kjsjksdds11',
	':created'  => ( new DateTime() )->format( "Y-m-d H:i:s" )
] );


var_dump($x->getLastInsertUuid());

//var_dump($sdb->getByUuidSeparateConnection( new \ShardMatrix\Uuid('06a00233-1ea85735-71b1-6034-a76e-444230303031')));
//
var_dump($sdb->getByUuidSeparateConnection( new \ShardMatrix\Uuid('06a00233-1ea85745-94a7-6a24-a90b-444230303033')));