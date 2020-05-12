<?php

use ShardMatrix\Db\Builder\DB;
use ShardMatrix\Db\Builder\QueryBuilder;
use ShardMatrix\Db\Builder\Schema;
use ShardMatrix\Db\Builder\ShardMatrixConnection;
use ShardMatrix\DB\Connections;
use ShardMatrix\DB\NodeQueries;
use ShardMatrix\DB\NodeQuery;
use ShardMatrix\DB\ShardDB;
use ShardMatrix\Dsn;
use ShardMatrix\NodeDistributor;
use ShardMatrix\ShardMatrix;

include './vendor/autoload.php';

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );
ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );
ShardMatrix::setNodeQueriesAsyncClass( \ShardMatrix\NodeQueriesGoThreaded::class );
ShardMatrix::setGeo( 'UK' );

$shardDb   = new ShardDB();
$statement = DB::allNodesTable( 'users' )
               ->orderBy( 'something', 'desc' )
               ->getPagination( [ "*" ], 1, 15 ,30);

//$statement = DB::allNodesTable( 'users')->where('username','like','randy%')->getPagination();
//$statement = DB::allNodesTable( 'users')->limit('10')->getPagination()->getResults();

var_dump( $statement->getResults()->fetchAllObjects() );




















//
//$shardDb->nodeQuery(
//	ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0001'),
//	"select uuid, username , ROW_NUMBER() OVER(ORDER BY uuid) as rowNum from users limit 10; "
//);
//
//var_dump($shardDb->allNodesQuery( 'users', "select uuid from users limit 100;",null,'uuid','asc'));

//DB::allNodesTable( 'users')->getPagination();
//


//var_dump(DB::allNodesTable( 'users' )->uuidMarkerPageAbove('06a00233-1ea82fe3-6a4d-6398-ab7b-444230303032')->getStatement()->fetchAllObjects());
//


//$f = ( new ShardDB() )->allNodesQuery( 'users', "ALTER TABLE users add created DATETIME null; " );
//$f = ( new ShardDB() )->allNodesQuery( 'users', "select * from users" ,null,'username','asc');
//var_dump($f->fetchRowArray());
//Schema::silent()->table( 'users', function(\Illuminate\Database\Schema\Blueprint $table){
//	$table->integer( 'something')->after( 'uuid');
//});
//DB::updateByUuid( '06a00233-1ea82fe3-4937-626c-8f1e-444230303032', ['password' => 'onlypoo12']);

//$shardDb = new ShardDB();
//echo DB::table( 'users')->sum('created'); die;
//$shardDb->nodeQuery( ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0007'), "create table users
//(
//    uuid     varchar(50)  not null
//        primary key,
//    username varchar(100) null,
//    password varchar(100) null,
//    email    varchar(200) null,
//    created  timestamp without time zone    null
//);");

//Schema::create( 'visitors',
//	function ( \Illuminate\Database\Schema\Blueprint $table ) {
//		$table->string( 'uuid', 50 )->primary();
//		$table->dateTime( 'created' );
//
//	} );

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
//while ( $i < 100 ) {
//	$username = 'randy' . rand( 5000, 10000000 ) . uniqid();
//	$password = 'cool!!' . rand( 5000, 100000 );
//	$email    = 'timmy' . rand( 1, 10000000 ) . uniqid() . '@google.com';
//	$created  = ( new DateTime() )->format( 'Y-m-d H:i:s' );
//	$i ++;
//	try {
//		\ShardMatrix\DB\Connections::closeConnections();
//		$shardDb = new ShardDB();
//		$shardDb->newNodeInsert( 'users', "insert into users  (uuid,username,password,email,created,something) values (:uuid,:username,:password,:email,:created,:something);", [
//			':username' => $username,
//			':password' => $password,
//			':email'    => $email,
//			':created'  => $created,
//			':something' => 4
//		] );
//	} catch ( \Exception $exception ) {
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


//$sdb = new ShardDB();
////
//$x = $sdb->insert( 'users', "insert into users ( uuid, username, email , password , created ) values (:uuid , :username, :email, :password, :created )", [
//	':username' => 'tim48135ff11',
//	':email'    => 'johfn@poo11.com',
//	':password' => 'kjsffjksdds11',
//	':created' => (new DateTime())->format('Y-m-d H:i:s')
//] );

//$user = new \ShardMatrix\DB\Models\EloquentDataRowModel( []);
//$user->setTable( 'users');
//$user->username = 'tank';
//$user->email = 'bill@fish.com';
//$user->created = (new DateTime())->format('Y-m-d H:i:s');
//$user->create();

//
//$x = DB::allNodesTable( 'users')->where( 'email','=','bill@fish.com')->get()->first();
////$x->username = 'tim48135';
////$x->save();
//var_dump( $x->username);

//$cache = new \ShardMatrix\PdoCache();
//$cache->runCleanPolicy( new ShardDB());
//$nowString = ( new DateTime() )->format( 'Y-m-d H:i:s' );
//
//var_dump( DB::table( 'users' )->insert( [
//	'username' => 'jackmaolne',
//	'password' => 'pooo',
//	'created'   => $nowString,
//	'email'    => 'jack.malone@yatti.com'
//] ) );
//
//var_dump(DB::allNodesThisGeoTable('users',null)->where( 'email','=','jack.malone@yatti.com')->first()->username);

//foreach ( DB::allNodesTable( 'users' )->orderBy( 'uuid', 'desc' )->getPagination()->getResults()->fetchDataRows() as $row ) {
//	echo $row->getUuid()->toString() . ' ' . $row->created . ' ' . PHP_EOL;
//	echo $row->getUuid()->getNode()->getName() . PHP_EOL;
//}
//$i = 0;
//foreach ( DB::allNodesTable( 'users' )->orderBy( 'uuid', 'desc' )->getPagination( ["*"],2)->getResults()->fetchDataRows() as $object ) {
//	$i ++;
//	echo $object->getUuid() . ' ' . $object->username . PHP_EOL;
//	echo $object->getUuid()->getNode()->getName() . ' ' . $object->created . PHP_EOL;
//	echo $object->getUuid()->getNode()->getDsn()->getConnectionType() . PHP_EOL;
//	echo 'result: ' . $i . PHP_EOL;
//	$object->something = 6;
//
//}

//Schema::table( 'users', function (\Illuminate\Database\Schema\Blueprint $table){
//	$table->timestamp( 'modified')->useCurrent();
//});
//
//$handle = fsockopen( "localhost", 1534 );
//
//$i = 0;
////while ($c = fgets($handle)){
////	echo $c.$i++;
////}
//try {
//	$client      = new \ShardMatrix\GoThreaded\Client();
//	$nodeQueries = new NodeQueries( [
//		new NodeQuery( ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0001' ), "select * from users where created > ? and created < ? limit 10000;", [
//			"2020-04-10 11:58:10",
//			"2020-04-21 11:58:10"
//		] ),
//		new NodeQuery( ShardMatrix::getConfig()->getNodes()->getNodeByName( 'DB0007' ), "select * from users where created > ? and created < ? limit 10000;", [
//			"2020-04-10 11:58:10",
//			"2020-05-04 11:58:10"
//		] )
//	] );
//
//	$client->execQueries( $nodeQueries )->getResults();
//	var_dump( $client->execQueries( $nodeQueries )->getResults() );
//} catch ( \Exception $e ) {
//	echo $e->getCode() . PHP_EOL;
//}


//echo json_encode( [ 'node_queries' => $nQs ] ) ;




