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

Recreate the dump without some tables
```
$ php extractor2.php dump.sql.gz --extract-except table1 (table2..tableN) output.sql.gz

```

Recreate the dump without some data (eg INSERT instructions)
```
$ php extractor2.php dump.sql.gz --extract-except-data table1 (table2..tableN) output.sql.gz
```

## Notes

The comand opptions could not be combined
