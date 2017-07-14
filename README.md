# ODBC SQL CMD
This script permit to execute one query or file containing queries to an OBDC database on Windows with commandline.
It uses PHP (php_odbc) to connect to database and send queries.
It downloads 7z and php for Windows.

# Help
```
=====================================================================================
  ##### ######  #######   ######    ######  ##### ####        ###### ###  ## ######  
 ###  ## ### ##  ###  ## ###  ##   ###  ## ###  ## ###       ###  ## ### ###  ### ## 
 ###  ## ###  ## ######  ###       ####    ###  ## ###       ###     #######  ###  ##
 ###  ## ###  ## ###  ## ###        #####  ###  ## ###       ###     ## # ##  ###  ##
 ###  ## ###  ## ###  ## ###          #### ### ### ###       ###     ## # ##  ###  ##
 ###  ## ### ##  ###  ## ###  ##   ##  ###  #####  ###   #   ###  ## ##   ##  ### ## 
  #####  #####   ######   #####     #####      ### #######    #####  ##   ##  #####  
=====================================================================================

Usage:
	odbc_sql.bat -d <ODBC Database> [-u <User>] [-p <Password>] -q <query>
	odbc_sql.bat -d <ODBC Database> [-u <User>] [-p <Password>] -f <file>
	odbc_sql.bat -d <ODBC Database> [-u <User>] [-p <Password>] [-q <query>|-f <file>] [-o <outputFile>] [-e outputFormat]

Options:
	-h	This help

	-d	ODBC Database
	-u	ODBC User
	-p	ODBC Password

	-q	Execute the query
	-f	Execute the queries in the file

	-o	Output file for the result
	-e	Output format for the result (default: txt): none, txt, csv, json, json_pretty
```
