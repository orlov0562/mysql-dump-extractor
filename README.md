# mysql-dump-extractor
Script that extracting particular tables from the MySQL dump

## Usage

Show "usage" tip
```
$ php extractor.php
```

Show list of the tables in the dump
```
$ php extractor.php dump.sql.gz --list
```

Extract tables to the "output.sql.gz" file
```
$ php extractor.php dump.sql.gz --extract table1 (table2..tableN) output.sql.gz
```
