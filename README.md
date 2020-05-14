

# ShardMatrix for PHP

### Database Sharding system for MYSQL and Postgres
* Requirements
    * PHP 7.4^
* Supports:
    * **A single Yaml configuration file**
    * **Multiple Nodes** (DB servers)
    * **Mysql**
    * **Postgres**
    * **Mysql & Postgres can be used together** and hot swapped
    * **Multiple Geo Locations**
    * **UUIDs** bakes in all relevant data for tables and on which node it belongs
    * **Docker**
    * **Kubernetes**
    * **Fast Asynchronous DB queries** (using a purpose built GoThreaded service https://github.com/jrsaunders/go-threaded | https://hub.docker.com/r/jrsaunders/gothreaded or PHP Forking )
    * Caching results to File or to Redis (Expandable to use any caching solution as Interfaces for this are available)
    * Unique table columns across nodes
    * Table Grouping to ensure data is kept in the right shards so joins can be done
    * Using popular ORM from Laravel ( though your project does **not** need be in Laravel ) https://laravel.com/docs/7.x
    * Query building being database agnostic
    * Efficient pagination system across Nodes using caching
    * Raw SQL Queries
    
# Installation

## Installing ShardMatrix for PHP

Use Composer to install ShardMatrix, or pull the repo from github.

```
composer require jrsaunders/shard-matrix
```

## Preparing the YAML config file

ShardMatrix needs to know how your tables and columns and databases interact, so this config file will define this in a simple yaml file.
* You will need your credentials for your databases, and access privileges setup.
[Reference Yaml file](shard_matrix.yaml)

### Example
This is a full example of how a configuration file should look.
```yaml
version: 1

table_groups:
  user:
    - users
    - payments
    - offers
  tracking:
    - visitors
    - sign_ups
  published:
    - published_offers

unique_columns:
  users:
    - email
    - username

nodes:
  DB0001:
    dsn: mysql:dbname=shard;host=localhost:3301;user=root;password=password
    docker_network: DB0001:3306
    geo: UK
    table_groups:
      - user
      - published
  DB0002:
    dsn: mysql:dbname=shard;host=localhost:3302;user=root;password=password
    docker_network: DB0002:3306
    geo: UK
    table_groups:
      - user
      - published
  DB0003:
    dsn: mysql:dbname=shard;host=localhost:3303;user=root;password=password
    docker_network: DB0003:3306
    geo: UK
    table_groups:
      - user
      - published
  DB0004:
    dsn: mysql:dbname=shard;host=localhost:3304;user=root;password=password
    docker_network: DB0004:3306
    geo: UK
    table_groups:
      - published
  DB0005:
    dsn: mysql:dbname=shard;host=localhost:3305;user=root;password=password
    docker_network: DB0005:3306
    table_groups:
      - tracking
  DB0006:
    dsn: mysql:dbname=shard;host=localhost:3306;user=root;password=password
    docker_network: DB0006:3306
    geo: UK
    insert_data: false
    table_groups:
      - tracking
  DB0007:
    dsn: pgsql:dbname=shard;host=localhost:5407;user=postgres;password=password
    docker_network: DB0007:5432
    geo: UK
    table_groups:
      - user
      - tracking

```
## Anatomy of the Configuration File

### Version
Define the version.  The most recent version is 1.
```yaml
version: 1
```
### Table Groups
Define the table groups.  As you add tables to your Application you will need to explicitly add them here to.

The group name is only used in ShardMatrix.

The table names are attributed to the groups.  A table can only be in one group at a time and once you have written to the Databases, it is best not to change any table assigned to a group.
* Denotes the table groups section on config
* Denotes the name of a group of tables
* Denotes the table name

```yaml
#Denotes the table groups section on config

table_groups:

  #Denotes the name of a group of tables

  user:

    #Denotes the table name

    - users
```
This section as it may appear.
```yaml
table_groups:
  user:
    - users
    - payments
    - offers
  tracking:
    - visitors
    - sign_ups
  published:
    - published_offers
```
### Unique Columns in Tables
Unique Columns can be defined here.  So in the `users` table `email` and `username` must be unique across all Nodes (shard databases).
```yaml
unique_columns:
  users:
    - email
    - username
  facebook_users:
    - fb_id
```
### Nodes

This is where you define your database connections, credentials, and what table groups and geos the node maybe using.

Nodes can be extended and added to as you go.

Node names must remain the same though as must the table groups they correspond to.

The anatomy of the node section.
* Denotes the where the nodes are defined
* Node Name
* DSN for connection to DB
* *optional Docker service name and port number
* *optional Geo - if a geo is stated the application inserting data will use this to choose this node to write new inserts to it
* *optional Stop new data being written here, unless connected to an existing UUID from this node
* Table groups that use this node must be defined here
* Table group user (that consists of the users, offers, payments tables)
```yaml
#Denotes the where the nodes are defined

nodes:

  #Node Name

  DBUK01:

    #DSN for connection to DB

    dsn: mysql:dbname=shard;host=localhost:3301;user=root;password=password

    # *optional docker service name and port number

    docker_network: DBUK:3306
    
    # *optional Geo - if a geo is stated the application inserting data will use this to choose this node to write new inserts to it

    geo: UK

    # *optional Stop new data being written here, unless connected to an existing UUID from this node

    insert_data: false

    #Table groups that use this node must be defined here

    table_groups:

      #Table group user (that consists of the users, offers, payments tables)

      - user
      
      - published
```
The Node Section as it may appear in the config yaml.
```yaml
nodes:
  DBUK01:
    dsn: mysql:dbname=shard;host=localhost:3301;user=root;password=password
    docker_network: DBUK:3306
    geo: UK
    table_groups:
      - user
      - published
  postg1:
    dsn: pgsql:dbname=shard;host=localhost:5407;user=postgres;password=password
    docker_network: postg1_db:5432
    table_groups:
      - tracking
  DB0001:
    dsn: mysql:dbname=shard;host=localhost:3304;user=root;password=password
    docker_network: DB0001:3306
    insert_data: false
    table_groups:
      - user
      - published
```

### Once Yaml Config File is Complete

Save the **file** to where the application is, in either a protected directory or externally inaccessible directory.

Alternatively it can be made into a **Kubernetes Secret** and given to your application that way.


# Initiate in PHP

In these examples we have saved our Config file as `shard_matrix.yaml` and placed it in the same directory as our applications index php.

#### Basic Setup Using Only PHP and Webserver Resources
* Our config file
* Specifying a local directory to write db data to when it needs to
```php

use ShardMatrix\ShardMatrix;


#Our config file

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );  


#Specifying a local directory to write db data to when it needs to

ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );  

```
#### Setup Using Only GoThreaded and Redis
* Our config file
* Changes the service from PHP forking for asynchronous queries to GoThreaded
* Uses GoThreaded for asynchronous DB calls when we have to query all relevant shards
* This overwrites the PdoCache Service that was using writing to file, and now instead uses Redis caching
```php

use ShardMatrix\ShardMatrix;


#Our config file

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );  


#Changes the service from PHP forking for asynchronous queries to GoThreaded

ShardMatrix::useGoThreadedForAsyncQueries();


#Uses GoThreaded for asynchronous DB calls when we have to query all relevant shards

ShardMatrix::setGoThreadedService( function () {
	return new \ShardMatrix\GoThreaded\Client( '127.0.0.1', 1534, 'gothreaded', 'password' );
} );

#This overwrites the PdoCache Service that was used to write to file, and now instead uses Redis caching

ShardMatrix::setPdoCacheService( function () {
	return new \ShardMatrix\PdoCacheRedis( new \Predis\Client( 'tcp://127.0.0.1:6379' ) );
} );  

```

# Quick Usage

Once you have initiated it as above - here are some quick examples of usage.

_If you are familiar with the ORM in Laravel - this is just an extension of that._

### Create Table
* Creates Table across all appropriate Nodes (Mysql and Postgres simultaneously).  This follows the guidance you have given in your Yaml Config file as to what tables belong on what nodes
```php
use ShardMatrix\Db\Builder\Schema;

#Creates Table across all appropriate Nodes (Mysql and Postgres simultaneously).
#This follows the guidance you have given in your Yaml Config file as to what tables
#belong on what nodes

Schema::create( 'users',
    function ( \Illuminate\Database\Schema\Blueprint $table ) {
          
        $table->string( 'uuid', 50 )->primary();
        $table->string('username',255)->unique();
        $table->string('email',255)->unique();
        $table->integer('something');
        $table->dateTime( 'created' );

    } 
);
```


### Insert Record
* Insert Data - the system will choose an appropriate shard node and create a UUID for it that will be attributed to an appropriate node
```php
use ShardMatrix\Db\Builder\DB;

#Insert Data - the system will choose an appropriate shard node and create a UUID for it that will be attributed to an appropriate node

DB::table( 'users' )->insert( 
    [
	'username' => 'jack-malone',
	'password' => 'poootpooty',
	'created'   => (new \DateTime())->format('Y-m-d H:i:s'),
	'something' => 5,
	'email'    => 'jack.malone@yatti.com',
    ]
);
```
**Inserted Data**
```
uuid        06a00233-1ea8af83-9b6f-6104-b465-444230303037
username    jack-malone
password    poootpooty
email       jack.malone@yatti.com
created     2020-04-30 15:35:31.000000
something   5
```

* Any further inserts done in this php process will be inserted into the same shard, if in the correct table group
