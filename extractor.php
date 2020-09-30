<?php
    /**
     * Author: Vitaly Orlov
     * Github: https://github.com/orlov0562/mysql-dump-extractor
     * Version: 2020-09-30
     */
     
    $usage = 'Usage: php '.basename(__FILE__).' <dump-file> (--list | --extract <table-1> <table-2> .. <table-n> <output-file>.sql.gz)';

    if (count($argv) == 1) {
        die($usage.PHP_EOL);
    }

    $gzipFile = $argv[1] ?? null;
    
    if (!$gzipFile || !file_exists($gzipFile)) {
        die('ERR: Dump file not found'.PHP_EOL);
    }

    $reader = function() use ($gzipFile) {
        $fh = gzopen ( $gzipFile , 'r');
        
        if (!$fh) {
            die('ERR: Can not open/read from file '.$gzipFile.PHP_EOL);
        }
        
        while(!feof($fh)) {
            yield gzgets($fh);
        }
        
        gzclose($fh);
    };
    
    $writer = function($filename, $data, $append=true) {
        $fh = gzopen ($filename , $append ? 'a' : 'w');
        if ($fh) {
            if (!is_array($data)) $data = [$data];
            foreach($data as $item) {
                gzwrite($fh, $item);
            }
            gzclose($fh);
        } else {
            die('ERR: Can not create/write to file '.$filename.PHP_EOL);
        }
    };
    
    $getTablesList = function($verbose=true) use ($reader) {
        
        $ret = [];
        
        $startTime = time();
        
        if ($verbose) {
            echo date('H:i:s').'] Found 0 table(s). Lines processed: 1, continue..'.PHP_EOL;
        }
        
        foreach($reader() as $k=>$line) {
            if (
                preg_match('~DROP TABLE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~CREATE TABLE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~INSERT[^`;+]INTO[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~UPDATE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~DELETE[^`;+]`([^`]+)`~Usi', $line, $regs) ||                
                preg_match('~Table structure for table[^`;+]`([^`]+)`~Usi', $line, $regs)
            ) {
                $ret[trim($regs[1])] = trim($regs[1]);
            }
            
            if ($verbose && (time() - $startTime) > 10) {
                $startTime = time();
                echo date('H:i:s').'] Found '.count($ret).' table(s). Lines processed: '.($k+1).', continue..'.PHP_EOL;
            }
        }
        
        if ($verbose) {
            echo date('H:i:s').'] Found '.count($ret).' table(s). Lines processed: '.($k+1).PHP_EOL;
        }
        
        sort($ret);
        
        return $ret;
    };
    
    $extractTables = function($tableList, $extractToFile, $verbose=true) use ($reader, $writer) {
        
        $ret = [];
        
        $startTime = time();
        
        echo date('H:i:s').'] Lines processed: 1, continue..'.PHP_EOL;
        
        $writer($extractToFile, '', false);
        
        $writeMarker = false;
        
        foreach($reader() as $k=>$line) {
            if (
                preg_match('~DROP TABLE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~CREATE TABLE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~INSERT[^`;+]INTO[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~UPDATE[^`;+]`([^`]+)`~Usi', $line, $regs) ||
                preg_match('~DELETE[^`;+]`([^`]+)`~Usi', $line, $regs) ||                
                preg_match('~Table structure for table[^`;+]`([^`]+)`~Usi', $line, $regs)
            ) {
                $table = trim($regs[1]);
                if (in_array($table, $tableList)) {
                    $writeMarker = true;
                } else {
                    $writeMarker = false;
                }
            }
            
            if ($writeMarker) {
                $writer($extractToFile, $line);
            }
            
            if ( $verbose && (time() - $startTime) > 10 ) {
                $startTime = time();
                echo date('H:i:s').'] Lines processed: '.($k+1).', continue..'.PHP_EOL;
            }
        }
        
        echo date('H:i:s').'] Lines processed: '.($k+1).PHP_EOL;
    };
    
    $action = $argv[2] ?? null;
    
    switch($action) {
        
        default:
            die('Undefined action'.PHP_EOL.$usage.PHP_EOL);
        break;
        
        case '--list':
            echo 'Looking for tables in the dump..'.PHP_EOL;
            $tableList = $getTablesList();
            
            echo 'Tables list in the dump:'.PHP_EOL;
            foreach($tableList as $table) {
                echo '- '.$table.PHP_EOL;
            }
        break;
        
        case '--extract':
     
            if (empty($argv[3])) {
                die('ERR: Not specified tables for extraction'.PHP_EOL.$usage.PHP_EOL);
            }
            
            if (empty($argv[4])) {
                die('ERR: Not specified output file'.PHP_EOL.$usage.PHP_EOL);
            }     

            $extractToFile = $argv[count($argv)-1];
            
            if (!preg_match('~\.sql\.gz~i',$extractToFile)) {
                die('ERR: Output file should have extension ".sql.gz", eg filename.sql.gz'.PHP_EOL.$usage.PHP_EOL);
            }     
            
            $extractTableList = array_slice($argv, 3, -1);
            
            echo str_repeat('-', 40).PHP_EOL;
            
            echo 'STEP 1: Tables to extract'.PHP_EOL;
            foreach($extractTableList as $table) {
                echo '- '.$table.PHP_EOL;
            }
            
            echo str_repeat('-', 40).PHP_EOL;
            
            echo 'STEP 2: Validating tables to extract'.PHP_EOL;
            echo 'Looking for tables in the dump..'.PHP_EOL;
            $tableList = $getTablesList();
            echo 'Validating tables..'.PHP_EOL;
            $hasError = false;
            foreach($extractTableList as $table) {
                echo '- '.$table.' .. ';
                if (in_array($table, $tableList)) {
                    echo 'FOUND'.PHP_EOL;
                } else {
                    echo 'NOT FOUND'.PHP_EOL;
                    $hasError = true;
                }
            }
            if ($hasError) {
                die('ERR: Some of the tables not found in the dump'.PHP_EOL);
            }
            
            echo str_repeat('-', 40).PHP_EOL;

            echo 'STEP 3: Extracting tables to the output file'.PHP_EOL;
            
            echo str_repeat('-', 40).PHP_EOL;
            
            $extractTables($extractTableList, $extractToFile);
            
            echo 'Complete!'.PHP_EOL;
            
        break;
    }
