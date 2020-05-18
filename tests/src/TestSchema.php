<?php


use PHPUnit\Framework\TestCase;
use ShardMatrix\DB\Builder\DB;
use ShardMatrix\DB\Builder\Schema;
use ShardMatrix\DB\Connections;
use ShardMatrix\DB\Interfaces\DBDataRowTransactionsInterface;
use ShardMatrix\ShardMatrix;
use ShardMatrix\Uuid;

class TestSchema extends TestCase {
	protected $uuid;

	protected function initGoThreaded() {
		ShardMatrix::initFromYaml( __DIR__ . '/../shard_matrix.yaml' );
		ShardMatrix::useGoThreadedForAsyncQueries();
		ShardMatrix::setPdoCacheService( function () {
			return new \ShardMatrix\PdoCacheRedis( new \Predis\Client( 'tcp://127.0.0.1:6386' ) );
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
				'something' => 4,
				'email'     => 'jack.malone@yatti.com',
			]
		);

		$this->assertTrue( $uuid instanceof Uuid, 'UUID Created' );

		$data = DB::getByUuid( $uuid );

		$this->assertTrue( ( $data->username == 'jack-malone' ), "Username Correct in inserted data" );


	}

	public function testGeneralDBUsage() {
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
		$count = DB::allNodesTable( 'users' )->count();
		$this->assertTrue( $count == 301, $count . ' inserted records' );
		$avg = DB::allNodesTable( 'users' )->avg( 'something' );
		$this->assertTrue( $avg == 4, $avg . ' average of something column' );
		$sum = DB::allNodesTable( 'users' )->sum( 'something' );
		$this->assertTrue( $sum == ( 300 * 4 ) + 4, $sum . ' sum of something column' );

		$pagination = DB::allNodesTable( 'users' )->getPagination( [ "*" ], 3, 15, 10 );
		$results    = $pagination->countResults();
		$this->assertTrue( $results == 152, $results . ' pagination results count' );
		$pages = $pagination->countPages();
		$this->assertTrue( $pages == 11, $pages . ' pagination pages count' );
		$nodes = [];
		foreach ( $pagination->getResults()->fetchDataRows() as $result ) {
			$uuid = $result->getUuid();
			$this->assertTrue( $uuid instanceof Uuid, 'Uuids are correct' );
			$nodes[ $uuid->getNode()->getName() ] = $uuid->getNode()->getName();
			$result->password                     = 'sillybilly69';
			$result->save();
			$this->uuid = $result->getUuid();
		}

		$this->assertTrue( count( $nodes ) == 4, 'Has Written to different Nodes' );
		$changeCount = DB::allNodesTable( 'users' )->where( 'password', '=', 'sillybilly69' )->count();
		$this->assertTrue( $changeCount == 15, $changeCount . ' update via transaction datarow' );

		$collection = DB::allNodesTable( 'users' )->where( 'username', 'like', 'randy%' )->get();
		$count      = $collection->count();
		$this->assertTrue( $count == 300, $count . ' collection of randy%' );
		$i = 0;
		$collection->each( function ( DBDataRowTransactionsInterface $record ) use ( &$i ) {
			$i ++;
			if ( $i % 2 == 0 ) {
				$record->delete();
			}
		} );

		$collection2 = DB::allNodesTable( 'users' )->where( 'username', 'like', 'randy%' )->get();
		$count       = $collection2->count();
		$this->assertTrue( $count == 150, $count . ' collection of randy% half count' );

		$pagination = DB::allNodesTable( 'users' )
		                ->orderBy( 'created', 'desc' )
		                ->paginate( $perPage = 15, $columns = [ '*' ], $pageName = 'page', $page = null );

		$pagination->each( function ( \ShardMatrix\DB\Interfaces\DBDataRowTransactionsInterface $record ) {

			$this->assertTrue( ( $record->getUuid() instanceof Uuid ), 'Pagination UUid instances' );
		} );

		$this->assertTrue( $pagination->total() == 151, $pagination->total() . ' Pagination Total' );
		$this->assertTrue( $pagination->perPage() == 15, $pagination->perPage() . ' Pagination Per Page' );

	}
}