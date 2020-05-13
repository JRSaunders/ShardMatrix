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

```
composer require jrsaunders/shard-matrix
```

## Preparing the YAML config file

[Reference Yaml file](shard_matrix.yaml)

State the version.  The most recent version is 1.
```yaml
version: 1
```
State the table groups.  As you add tables to your Application you will need to explicitly add them here to.

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
Unique Columns can be stated here.  So in the `users` table `email` and `username` must be unique across all Nodes (shard databases).
```yaml
unique_columns:
  users:
    - email
    - username
```
    
