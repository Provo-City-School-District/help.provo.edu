# help.provo.edu
## Default Ports
these are the default ports that the containers will use. If you need to change them, you can do so in the docker-compose.yml file.
- 8080 - HTTP
- 8085 - PHPMyAdmin
- 3310 - MySQL

## Requirements
- Requires Docker Engine and Docker Compose to be installed on the host machine.
- Requires a .env file in the root directory with the following variables set. make sure to wrap passwords in quotes to prevent syntax errors when they are loaded into CLI variables.

```
LDAPHOST=
LDAPPORT=
LDAP_DN=
LDAP_USER=
LDAP_PASS=
SQL_ROOT=
HELPMYSQL_USER=
HELPMYSQL_PASSWORD=
HELPMYSQL_DATABASE=
HELPMYSQL_HOST=
HELPMYSQL_PORT=
VAULT_READ_HOST=
VAULT_READ_USER=
VAULT_READ_PASSWORD=
VAULT_READ_DATABASE=
ROOTDOMAIN=
UDPGRAYLOGHOST=
GRAYLOGIP=
GRAYLOGPORT=
SWHELPDESKHOST=
SWHELPDESKUSER=
SWHELPDESKPASSWORD=
SWHELPDESKDATABASE=
GMAIL_USER=
GMAIL_PASSWORD=
GOOG_SSO_ID=
GOOG_SSO_SECRET=
GOOG_SSO_REDIRECT=
```
## Control Commands
following commands must be run from within the root directory of the project.
### Build
```docker compose build```

### Start
```docker compose up -d```

### Stop
```docker compose down```

### Restart
```docker compose restart```


## Restore / Development
Clone the repository to your server with docker engine installed
```
git clone https://github.com/Provo-City-School-District/help.provo.edu.git
```

Change to the help.provo.edu directory.

If you're restoring from backup, get a copy of the backup database dump from Barracuda and restore it into the database using PHPmyadmin.

If you're setting up a fresh development instance get a copy of the database dump from one of the other developers and restore it into the database using PHPmyadmin.(planned to create the database on first run in the future)

# Resources Used
- Docker: https://www.docker.com/
- Docker Compose: https://docs.docker.com/compose/
- PHP: https://www.php.net/
- PHPMyAdmin: https://www.phpmyadmin.net/
- MariaDB: https://mariadb.org/
- Data Tables: https://datatables.net/
- TinyMCE: https://www.tiny.cloud/docs/tinymce/6/
- CSS Alerts: https://alvarotrigo.com/blog/css-alerts/
- CanvasJS: https://canvasjs.com/php-charts/