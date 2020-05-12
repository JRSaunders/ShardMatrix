# ShardMatrix for PHP

### Database Sharding system for MYSQL and Postgres

* Supports:
    * A single Yaml configuration file
    * Multiple Nodes (DB servers)
    * Mysql
    * Postgres
    * Mysql & Postgres can be used together and hot swapped
    * Multiple Geo Locations
    * Fast Asynchronous DB queries (using a purpose built GoThreaded service https://github.com/jrsaunders/go-threaded or PHP Forking )
    * UUIDs bakes in all relevant data for tables and on which node it belongs
    * Unique table columns across nodes
    * Table Grouping to ensure data is kept in the right shards so joins can be done
    * Using the popular ORM from Laravel ( though your project does not need be in Laravel )
    * QueryBuilding being database agnostic
    * Efficient pagination system across Nodes
    * Raw SQL Queries
    * Docker
    * Kubernetes
    


    
