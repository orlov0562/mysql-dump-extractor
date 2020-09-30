# mysql-dump-extractor
Script that extracting particular tables from the MySQL dump

## Usage
```
$ php extractor.php = Show "usage" tip

$ php extractor.php dump.sql.gz --list = Show list of the tables in the dump

$ php extractor.php dump.sql.gz --extract table1 (table2..tableN) output.sql.gz = Extract tables to the "output.sql.gz" file

```
