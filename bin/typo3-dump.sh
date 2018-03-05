#!/bin/bash

getopts=`getopt -o d:u:p::h:cf: --long database:,user:,password::,host:,compress,file:,syntax,help -n "$0" -- "$@"`

mysql_user="";
mysql_pw="";
mysql_db="";
mysql_host="localhost";

FILE="";
COMPRESS="";


function syntaxHelp {
        echo "Syntax is like mysqldump -d -u -p";
}


eval set -- "$getopts"
while true ; do
    case "$1" in
        -d|--database)
		mysql_db=$2;
		shift 2;;
	-u|--user)
		mysql_user=$2;
		shift 2;;
	-p|--password)
		mysql_pw="${2#"${2%%[![:space:]]*}"}";
		if [[ "$mysql_pw" -eq "" ]];
		then
			read -s -p "MySQL Password:" mysql_pw;
		fi
		shift 2;;
	-h|--host)
		mysql_host=$2;
		shift 2;;
	-c|--compress)
		COMPRESS=1;
		shift;;
	-f|--file)
		FILE=$2;
		shift 2;;
        --) shift; break;;
	*|--syntax|--help)
		syntaxHelp;
		exit 1;;
    esac
done

DIR=`pwd`;
TABLE_EXCLUDE_REGEXP="^(cache_.+|cf_.+|cachingframework_.+|^zzz_.+)";

TSTAMP=$(date +%Y%m%d-%H%M);

if [[ ! $FILE ]];
then
	FILE="$DIR/$TSTAMP-$mysql_db.sql";
fi

if [[ ! -z $COMPRESS ]];
then
	FILE+=".bz2";
fi

FILE=$(realpath $FILE);

if [[ ! $FILE ]] || [[ ! $mysql_db ]];
then
	echo -e "Skipping ... Parameters incomplete!\n";
	echo -e "DB:\t'$mysql_db'";
	echo -e "User:\t'$mysql_user'";
	echo -e "Host:\t'$mysql_host'";
	echo -e "File:\t'$FILE'";
	echo "";
	syntaxHelp;
	exit 1;
fi

echo -e "Create DB-DUMP \n... for '$mysql_db'\n... to '$FILE'?\nConfirm dump [y/n]";


function startDump {
	if [[ -z $COMPRESS ]];
	then
		mysqldump $mysql_db -u $mysql_user -p$mysql_pw -h $mysql_host --opt "$(getExcludeTables)" > $FILE;
	else
		mysqldump $mysql_db -u $mysql_user -p$mysql_pw -h $mysql_host --opt "$(getExcludeTables)" | bzip2 > $FILE;
	fi
}


function getExcludeTables {
	ignoreTables=$(echo mysql \
		-u $mysql_user -p\'$mysql_pw\' -h $mysql_host \
		--database $mysql_db -Ns \
		--execute=\""SELECT GROUP_CONCAT(TABLE_NAME) FROM information_schema.tables WHERE TABLE_SCHEMA='${mysql_db}' AND TABLE_NAME REGEXP('${TABLE_EXCLUDE_REGEXP}')"\" \
	);

	array=(${ignoreTables//,/ });
	ignoreTableParam="";
	for tableName in "${array[@]}"
	do
		ignoreTableParam+=" --ignore-table=${DB_NAME}.${tableName}";
	done

	echo $ignoreTableParam;
}

while true; do
	read -r -s -n1 response

	if [[ $response =~ ^([yY])$ ]]
	then
		echo -e "\n Creating '$FILE' ...";
		startDump;
		exit 0;
	elif [[ $response =~ ^([nN])$ ]]
	then
		echo "Skipped!";
		exit 0
	fi
done
