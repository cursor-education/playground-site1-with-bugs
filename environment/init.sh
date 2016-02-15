#!/bin/bash -e

# restart services
/sbin/service httpd restart
/sbin/service mongod restart

/bin/bash