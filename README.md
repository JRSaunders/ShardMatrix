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

Define the version.  The most recent version is 1.
```yaml
version: 1
```
### Table Groups
Define the table groups.  As you add tables to your Application you will need to explicitly add them here to.

The group name is only used in ShardMatrix.

The table names are attributed to the groups.  A table can only be in one group at a time and once you have written to the Databases, it is best not to change any table assigned to a group.
```yaml
table_groups: #denotes the table groups section on config
  user: #denotes the name of a group of tables
    - users #denotes the table name
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
```yaml
nodes: #denotes the where the nodes are defined
  DBUK01: #Node Name
    dsn: mysql:dbname=shard;host=localhost:3301;user=root;password=password #DSN for connection to DB
    docker_network: DBUK:3306 # *optional docker service name if you have one and port
    geo: UK # *optional geo - if a geo is stated the application inserting data will use this to choose this node to write new inserts to it
    insert_data: false # *optional stop new data being written here, unless connected to an existing UUID from this node
    table_groups: #table groups that use this node must be defined here
      - user #table group user (that comprises of the users, offers, payments tables)
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

