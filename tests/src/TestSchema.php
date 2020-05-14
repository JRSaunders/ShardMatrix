<?php


use PHPUnit\Framework\TestCase;
use ShardMatrix\DB\Builder\DB;
use ShardMatrix\DB\Builder\Schema;
use ShardMatrix\DB\Connections;
use ShardMatrix\ShardMatrix;

class TestSchema extends TestCase {

	protected function initGoThreaded() {
		ShardMatrix::initFromYaml( __DIR__ . '/../shard_matrix.yaml' );
		ShardMatrix::useGoThreadedForAsyncQueries();
		ShardMatrix::setPdoCacheService( function () {
			return new \ShardMatrix\PdoCacheRedis( new \Predis\Client( 'tcp://pdocache:6386' ) );
		} );
		ShardMatrix::setGoThreadedService( function () {
			return new \ShardMatrix\GoThreaded\Client( '127.0.0.1', 1541, 'gothreaded', 'password', 20 );
		} );
	}

	protected function initFork() {
		ShardMatrix::initFromYaml( __DIR__ . '/../shard_matrix.yaml' );
		ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );
		ShardMatrix::usePhpForkingForAsyncQueries();
		ShardMatrix::setPdoCacheService( function () {
			return new \ShardMatrix\PdoCache();
		} );
	}

	public function testSchemas() {
		$this->initGoThreaded();
		try {
			Schema::create( 'users',
				function ( \Illuminate\Database\Schema\Blueprint $table ) {

					$table->string( 'uuid', 50 )->primary();
					$table->string( 'username', 255 )->unique();
					$table->string( 'email', 255 )->unique();
					$table->string( 'password', 255 );
					$table->integer( 'something' );
					$table->dateTime( 'created' );

				}
			);
		} catch ( \ShardMatrix\DB\Builder\BuilderException $exception ) {
			//do nothing
		}
		$uuid = DB::table( 'users' )->insert(
			[
				'username'  => 'jack-malone',
				'password'  => 'poootpooty',
				'created'   => ( new \DateTime() )->format( 'Y-m-d H:i:s' ),
				'something' => 5,
				'email'     => 'jack.malone@yatti.com',
			]
		);

		$this->assertTrue( $uuid instanceof \ShardMatrix\Uuid, 'UUID Created' );

		$data = DB::getByUuid( $uuid );

		$this->assertTrue( ( $data->username == 'jack-malone' ), "Username Correct in inserted data" );


	}

	public function testNodeInserts() {
		$i = 0;
		while ( $i < 300 ) {
			$username = 'randy' . rand( 5000, 10000000 ) . uniqid();
			$password = 'cool!!' . rand( 5000, 100000 );
			$email    = 'timmy' . rand( 1, 10000000 ) . uniqid() . '@google.com';
			$created  = ( new DateTime() )->format( 'Y-m-d H:i:s' );
			$i ++;
			try {

				DB::shardTable( 'users' )->insert( [
					'username'  => $username,
					'password'  => $password,
					'email'     => $email,
					'created'   => $created,
					'something' => 4
				] );
			} catch ( \ShardMatrix\DB\Builder\BuilderException $exception ) {
				echo $exception->getNode()->getName() . ' ' . $exception->getMessage() . PHP_EOL;
			}
		}
		$count = DB::table( 'users' )->count();
		$this->assertTrue( DB::allNodesTable( 'users' )->count() == 500 ,$count.' inerted records');
	}
}