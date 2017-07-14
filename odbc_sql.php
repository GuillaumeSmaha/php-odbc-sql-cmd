#!/usr/bin/env php
<?php

// Guillaume Smaha
// https://github.com/GuillaumeSmaha

// Set time limit to 30min
ini_set('max_execution_time', 3600);
ini_set('max_input_time', 3600);
set_time_limit(3600);

// Set memory size limit to 1024Mo
ini_set('memory_limit', '1024M');


define('MODE_QUERY', 'query');
define('MODE_FILE', 'file');

define('FORMAT_NONE', 'NONE');
define('FORMAT_TXT', 'txt');
define('FORMAT_CSV', 'csv');
define('FORMAT_JSON', 'json');
define('FORMAT_JSON_PRETTY', 'json_pretty');

$_FORMATS = [
	FORMAT_NONE,
	FORMAT_TXT,
	FORMAT_CSV,
	FORMAT_JSON,
	FORMAT_JSON_PRETTY,
];

$_OPTIONS = [
	'odbc' => [
		'server' => '',
		'user' => '',
		'pass' => '',
		'nb_connection' => '1',
	],
	'mode' => '',
	'query' => '',
	'file' => '',
	'output' => 'php://output',
	'outputFormat' => FORMAT_TXT,
];



function utf16_to_utf8($str) {
    //http://www.moddular.org/log/utf16-to-utf8
    //16th March 2006
    $c0 = ord($str[0]);
    $c1 = ord($str[1]);
    if ($c0 == 0xFE && $c1 == 0xFF) {
        $be = true;
    } else if ($c0 == 0xFF && $c1 == 0xFE) {
        $be = false;
    } else {
        return $str;
    }
    $str = substr($str, 2);
    $len = strlen($str);
    $dec = '';
    for ($i = 0; $i < $len; $i += 2) {
        $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) :
                ord($str[$i + 1]) << 8 | ord($str[$i]);
        if ($c >= 0x0001 && $c <= 0x007F) {
            $dec .= chr($c);
        } else if ($c > 0x07FF) {
            $dec .= chr(0xE0 | (($c >> 12) & 0x0F));
            $dec .= chr(0x80 | (($c >>  6) & 0x3F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        } else {
            $dec .= chr(0xC0 | (($c >>  6) & 0x1F));
            $dec .= chr(0x80 | (($c >>  0) & 0x3F));
        }
    }
    return $dec;
}

function microtime_float() {
    list($utime, $time) = explode(" ", microtime());
    return ((float)$utime + (float)$time);
}

function help() {

	$baseName = str_replace('.php', '.bat', basename($_SERVER['argv'][0]));

	echo "=====================================================================================\n";
	echo "  ##### ######  #######   ######    ######  ##### ####        ###### ###  ## ######  \n";
	echo " ###  ## ### ##  ###  ## ###  ##   ###  ## ###  ## ###       ###  ## ### ###  ### ## \n";
	echo " ###  ## ###  ## ######  ###       ####    ###  ## ###       ###     #######  ###  ##\n";
	echo " ###  ## ###  ## ###  ## ###        #####  ###  ## ###       ###     ## # ##  ###  ##\n";
	echo " ###  ## ###  ## ###  ## ###          #### ### ### ###       ###     ## # ##  ###  ##\n";
	echo " ###  ## ### ##  ###  ## ###  ##   ##  ###  #####  ###   #   ###  ## ##   ##  ### ## \n";
	echo "  #####  #####   ######   #####     #####      ### #######    #####  ##   ##  #####  \n";
	echo "=====================================================================================\n";
	echo "\n";
	echo "Usage:\n";
	echo "\t".$baseName." -d <ODBC Database> [-u <User>] [-p <Password>] -q <query>\n";
	echo "\t".$baseName." -d <ODBC Database> [-u <User>] [-p <Password>] -f <file>\n";
	echo "\t".$baseName." -d <ODBC Database> [-u <User>] [-p <Password>] [-q <query>|-f <file>] [-o <outputFile>] [-e outputFormat]\n";
	echo "\n";
	echo "Options:\n";
	echo "\t-h\tThis help\n";
	echo "\n";
	echo "\t-d\tODBC Database\n";
	echo "\t-u\tODBC User\n";
	echo "\t-p\tODBC Password\n";
	echo "\n";
	echo "\t-q\tExecute the query\n";
	echo "\t-f\tExecute the queries in the file\n";
	echo "\n";
	echo "\t-o\tOutput file for the result\n";
	echo "\t-e\tOutput format for the result (default: txt): none, txt, csv, json, json_pretty\n";
}

function readArgs() {
	global $_OPTIONS;

	$options = getopt("hd:u:p:f:q:o:e:");

	foreach ($options as $key => $value) {
		switch ($key) {
			case 'h':
				help();
				exit(0);
				break;
			case 'd':
				$_OPTIONS['odbc']['server'] = trim($value);
				break;
			case 'u':
				$_OPTIONS['odbc']['user'] = trim($value);
				break;
			case 'p':
				$_OPTIONS['odbc']['pass'] = trim($value);
				break;
			case 'q':
				$_OPTIONS['mode'] = MODE_QUERY;
				$_OPTIONS['query'] = trim($value);
				break;
			case 'f':
				$_OPTIONS['mode'] = MODE_FILE;
				$_OPTIONS['file'] = trim($value);
				break;
			case 'o':
				$_OPTIONS['output'] = trim($value);
				break;
			case 'e':
				$_OPTIONS['outputFormat'] = trim($value);
				break;
			default:
				help();
				echo "\n\n";
				echo "Option ".$key." is unknow\n";
				exit(1);
				break;
		}
	}
}

function checkOptions() {
	global $_OPTIONS;
	global $_FORMATS;

	if (strlen($_OPTIONS['odbc']['server']) === 0) {
		help();
		echo "\n\n";
		echo "Option -d is mandatory\n";
			exit(1);
	}
	
	if (strlen($_OPTIONS['mode']) === 0) {
		help();
		echo "\n\n";
		echo "Option -q or -f is mandatory\n";
		exit(1);
	}

	if ($_OPTIONS['mode'] === MODE_FILE) {
		if (!file_exists($_OPTIONS['file'])) {
			help();
			echo "\n\n";
			echo "File '".$_OPTIONS['file']."' was not found !";
			exit(1);
		}
	}

	if (!in_array($_OPTIONS['outputFormat'], $_FORMATS)) {
		help();
		echo "\n\n";
		echo "Output format '".$_OPTIONS['outputFormat']."' is not available !";
		exit(1);
	}
}

function odbcConnectionOpen() {
	global $_OPTIONS;

	$conn = @odbc_connect($_OPTIONS['odbc']['server'], $_OPTIONS['odbc']['user'], $_OPTIONS['odbc']['pass']);
	if (!$conn) {
		echo "Failed to open connection on '".$_OPTIONS['odbc']['server']."':\n";
		echo "\t".odbc_errormsg();
		exit(1);
	}
	return $conn;
}

function odbcConnectionExec($conn, $query) {
	$result = odbc_exec($conn, $query);
	if ($result === false) {
		echo "Error during excution of query:\n";
		echo "\t".$query;
		echo odbc_errormsg($result);
		exit(2);
	}

	return $result;
}

function odbcConnectionClose($conn) {
	global $_OPTIONS;

	if (!empty($conn)) {
		odbc_close($conn);
	}
}



function outputOpen() {
	global $_OPTIONS;

	$_OPTIONS['outputFT'] = fopen($_OPTIONS['output'], 'w');
}


function outputClose() {
	global $_OPTIONS;

	if (!empty($_OPTIONS['outputFT'])) {
		fclose($_OPTIONS['outputFT']);
		unset($_OPTIONS['outputFT']);
	}
}


function outputLine($row) {
	global $_OPTIONS;

	switch ($_OPTIONS['outputFormat']) {
		case FORMAT_TXT:
			fwrite($_OPTIONS['outputFT'], implode(",\t", $row)."\n");
			break;
		case FORMAT_CSV:
			fputcsv($_OPTIONS['outputFT'], $row);
			break;
		case FORMAT_JSON:
		case FORMAT_JSON_PRETTY:
			return $row;
			break;
	}

	return null;
}

function outputAllData($data) {
	global $_OPTIONS;

	switch ($_OPTIONS['outputFormat']) {
		case FORMAT_TXT:
		case FORMAT_CSV:
			break;
		case FORMAT_JSON:
			fwrite($_OPTIONS['outputFT'], json_encode($data));
			break;
		case FORMAT_JSON_PRETTY:
			fwrite($_OPTIONS['outputFT'], json_encode($data,  JSON_PRETTY_PRINT));
			break;
	}
}


readArgs();

checkOptions();

$timeStart = microtime_float();

if ($_OPTIONS['mode'] === MODE_QUERY) {
	$conn = odbcConnectionOpen();

	$result = odbcConnectionExec($conn, $_OPTIONS['query']);

	if ($_OPTIONS['outputFormat'] !== FORMAT_NONE) {
		outputOpen();
		$data = [];
		while(@odbc_fetch_row($result)) {
			$dataRow = [];
			for ( $i = 1 ; $i <= odbc_num_fields($result) ; $i++)
			{
				$value = odbc_result($result, $i);
				$dataRow[] = $value;
			}
			$data[] = outputLine($dataRow);
		}
		outputAllData($data);	
		outputClose();
	}

	odbcConnectionClose($conn);
}
else if($_OPTIONS['mode'] === MODE_FILE) {

	$file = file_get_contents($_OPTIONS['file']);

	// Convert from utf16 to utf8 character encoding
	echo "Convert file from utf-16 to utf-8 ... ";
	$file = utf16_to_utf8($file);
	echo "Done\n";

	// Strip out errant MS line endings
	echo "Clean file content ... ";
	$file = str_replace(array("\r", "\r\n", "\r\n", "\n\n"), "\n", $file);
	while (strpos($file, "\n\n") !== false) {
		$file = str_replace("\n\n", "\n", $file);
	}
	$file = trim($file);
	echo "Done\n";

	if (strlen($file) === 0) {
		echo "\n";
		echo "File is empty !";
		exit(3);
	}

	$conn = odbcConnectionOpen();

	$data = [];
	if ($_OPTIONS['outputFormat'] !== FORMAT_NONE) {
		outputOpen();
	}
	
	echo "Import file ...\n";

	$timeTotal = 0;
	$recordTotal = 0;
	$sep = "\n";
	$last = false;
	while(true) {
		$queryStart = microtime_float();
		$posEnd = strpos($file, $sep, strlen($sep));

		if ($posEnd === false && strlen($file) > 0) {
			$query = $file;
			$posEnd = strlen($file);
		}
		$query = trim(substr($file, 0, $posEnd));
		$file = substr($file, $posEnd);

		$result = odbcConnectionExec($conn, $query);
		
		if ($_OPTIONS['outputFormat'] !== FORMAT_NONE) {
			while(@odbc_fetch_row($result)) {
				$dataRow = [];
				for ( $i = 1 ; $i <= odbc_num_fields($result) ; $i++)
				{
					$value = odbc_result($result, $i);
					$dataRow[] = $value;
				}
				$data[] = outputLine($dataRow);
			}
		}
		
		$timeQuery = bcsub(microtime_float(), $queryStart, 4);
		$timeTotal += $timeQuery;
		$recordTotal ++;
		echo "\t1 records processed.  Portion: ".$timeQuery." Total: ".$timeTotal."\n";
	
		if(empty($file) || strlen($file) <= strlen($sep) || $posEnd === false) {
			break;
		}
	}
	
	echo "Import file with ".$recordTotal." records ... Done\n";

	odbcConnectionClose($conn);

	if ($_OPTIONS['outputFormat'] !== FORMAT_NONE) {
		outputAllData($data);	

		outputClose();
	}
}

$timeTotal = bcsub(microtime_float(), $timeStart, 4);

echo "Total excution time: ".$timeTotal;

exit(0);
?>