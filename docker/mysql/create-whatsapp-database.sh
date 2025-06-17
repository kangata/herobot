#!/usr/bin/env

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`${WA_DB_DATABASE}\`;
    GRANT ALL PRIVILEGES ON \`${WA_DB_DATABASE}\`.* TO '$MYSQL_USER'@'%';
    
    USE \`${WA_DB_DATABASE}\`;
    CREATE TABLE IF NOT EXISTS \`${WA_DB_TABLE_NAME}\` (
        \`session\` varchar(50) NOT NULL, 
        \`id\` varchar(80) NOT NULL, 
        \`value\` json DEFAULT NULL, 
        UNIQUE KEY \`idxunique\` (\`session\`,\`id\`), 
        KEY \`idxsession\` (\`session\`), 
        KEY \`idxid\` (\`id\`)
    ) ENGINE=MyISAM;
EOSQL