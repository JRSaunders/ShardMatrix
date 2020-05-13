# ShardMatrix for PHP

### Database Sharding system for MYSQL and Postgres
* Requirements
    * PHP 7.4^
* Supports:
    * A single Yaml configuration file
    * Multiple Nodes (DB servers)
    * Mysql
    * Postgres
    * Mysql & Postgres can be used together and hot swapped
    * Multiple Geo Locations
    * Fast Asynchronous DB queries (using a purpose built GoThreaded service https://github.com/jrsaunders/go-threaded | https://hub.docker.com/r/jrsaunders/gothreaded or PHP Forking )
    * Caching results to File or to Redis (Expandable to use any caching solution as Interfaces for this are available)
    * UUIDs bakes in all relevant data for tables and on which node it belongs
    * Unique table columns across nodes
    * Table Grouping to ensure data is kept in the right shards so joins can be done
    * Using popular ORM from Laravel ( though your project does **not** need be in Laravel ) https://laravel.com/docs/7.x
    * QueryBuilding being database agnostic
    * Efficient pagination system across Nodes using caching
    * Raw SQL Queries
    * Docker
    * Kubernetes
    
# Guide

## Installing ShardMatrix for PHP

```
composer require jrsaunders/shard-matrix
```

    
