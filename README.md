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
    
# Guide

## Installing ShardMatrix for PHP

Use Composer to install ShardMatrix, or pull the repo from github.

```
composer require jrsaunders/shard-matrix
```

## Preparing the YAML config file

ShardMatrix needs to know how your tables and columns and databases interact, so this config file will define this in a simple yaml file.

[Reference Yaml file](shard_matrix.yaml)

### Example

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
table_groups:  #Denotes the table groups section on config
  user:  #Denotes the name of a group of tables
    - users  #Denotes the table name
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

Nodes can extended and added to as you go.

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
nodes:  #Denotes the where the nodes are defined

  DBUK01:  #Node Name

    dsn: mysql:dbname=shard;host=localhost:3301;user=root;password=password  #DSN for connection to DB

    docker_network: DBUK:3306  # *optional docker service name and port number
    
    geo: UK # *optional Geo - if a geo is stated the application inserting data will use this to choose this node to write new inserts to it
    
    insert_data: false # *optional Stop new data being written here, unless connected to an existing UUID from this node
    
    table_groups: #Table groups that use this node must be defined here
      
      - user #Table group user (that consists of the users, offers, payments tables)
      
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

Save the file to either where the application in a protected or externally inaccessible directory.

Alternatively it can be made into a Kubernetes Secret and given to your application that way.


## Initiate in PHP

In these examples we have saved our Config file as `shard_matrix.yaml` and placed it in the same directory as our applications index php.

#### Basic Setup Using Only PHP and Webserver Resources
* Our config file
* Specifying a local directory to write db data to when it needs to
```php

use ShardMatrix\ShardMatrix;

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );  #Our config file

ShardMatrix::setPdoCachePath( __DIR__ . '/shard_matrix_cache' );  #Specifying a local directory to write db data to when it needs to

```
#### Setup Using Only GoThreaded and Redis
* Our config file
* Changes the service from PHP forking for asynchronous queries to GoThreaded
* Uses GoThreaded for asynchronous DB calls when we have to query all relevant shards
* This overwrites the PdoCache Service that was using writing to file, and now instead uses Redis caching
```php

use ShardMatrix\ShardMatrix;

ShardMatrix::initFromYaml( __DIR__ . '/shard_matrix.yaml' );  #Our config file

ShardMatrix::useGoThreadedForAsyncQueries();# Changes the service from PHP forking for asynchronous queries to GoThreaded

ShardMatrix::setGoThreadedService( function () {
	return new \ShardMatrix\GoThreaded\Client( '127.0.0.1', 1534, 'gothreaded', 'password' );
} );  #Uses GoThreaded for asynchronous DB calls when we have to query all relevant shards

ShardMatrix::setPdoCacheService( function () {
	return new \ShardMatrix\PdoCacheRedis( new \Predis\Client( 'tcp://127.0.0.1:6379' ) );
} );  #This overwrites the PdoCache Service that was using writing to file, and now instead uses Redis caching

```
