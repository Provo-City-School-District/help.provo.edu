# help.provo.edu
Version: 1.1.02
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
SMTP_HOST=
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

Change to the help.provo.edu directory and build your containers with ```docker compose build``` then start the containers with ```docker compose up -d```

If you're restoring from backup, get a copy of the backup database dump from Barracuda and restore it into the database using PHPmyadmin.

If you're setting up a fresh development instance the database should initialize for you, but you may want to get a database dump from backup or a fellow developer to get data to work with.

# Resources Used
- Docker: https://www.docker.com/
- Docker Compose: https://docs.docker.com/compose/
- PHP: https://www.php.net/
- PHPMyAdmin: https://www.phpmyadmin.net/
- MariaDB: https://mariadb.org/
- CanvasJS: https://canvasjs.com/php-charts/
- Composer: https://getcomposer.org/
- Twig (3.x): https://twig.symfony.com/
- PHPMailer (6.9.1): https://github.com/PHPMailer/PHPMailer
- Data Tables (2.0.7): https://datatables.net/
- TinyMCE (7.1): https://www.tiny.cloud/docs/tinymce/6/
- HTMLPurifier (4.17): https://htmlpurifier.org/
- Jquery (3.7.1): https://jquery.com/
- Jquery UI (1.13.3): https://jqueryui.com/
- Lightbox2 (2.11.4) - https://github.com/lokesh/lightbox2
