FROM centos:6
MAINTAINER itspoma <itspoma@gmail.com>

RUN yum clean all
RUN yum install -y git curl mc
RUN yum install -y libcurl-devel openssl openssl-devel
RUN yum install -yv gcc-c++ make

# apache2
RUN yum install -y httpd

# configure the httpd
RUN sed 's/#ServerName.*/ServerName demo/' -i /etc/httpd/conf/httpd.conf \
 && sed 's/#EnableSendfile.*/EnableSendfile off/' -i /etc/httpd/conf/httpd.conf

# php 5.5
RUN rpm -Uvh http://mirror.webtatic.com/yum/el6/latest.rpm \
 && yum install -y php55w php55w-devel php55w-pdo php55w-mysql php55w-intl php55w-pear

# configure the php.ini
RUN echo "" >> /etc/php.ini \
 && sed 's/;date.timezone.*/date.timezone = Europe\/Kiev/' -i /etc/php.ini \
 && sed 's/^display_errors.*/display_errors = On/' -i /etc/php.ini \
 && sed 's/^display_startup_errors.*/display_startup_errors = On/' -i /etc/php.ini \
 && sed 's/^upload_max_filesize.*/upload_max_filesize = 8M/' -i /etc/php.ini \
 && sed 's/^;error_log.*/error_log = \/shared\/logs\/php.log/' -i /etc/php.ini

# put vhost config for httpd
ADD ./environment/httpd/site.conf /etc/httpd/conf.d/site.conf

# install Composer
RUN curl -sS https://getcomposer.org/installer | php \
 && mv composer.phar /usr/local/bin/composer

# nodejs
RUN curl --silent --location https://rpm.nodesource.com/setup | bash - \
 && yum -y install nodejs \
 && npm -g install npm@latest

# ruby
# RUN true \
 # && yum -y install ruby ruby-devel rubygems \
 # && gem install --no-rdoc --no-ri sass

# bower
# RUN true \
 # && npm install -g bower \
 # && npm install -g grunt-cli

RUN printf "\n" | pecl install mongo mongodb \
 && echo "extension=mongo.so" > /etc/php.d/mongo.ini \
 && echo "extension=mongodb.so" >> /etc/php.d/mongo.ini \
 && ls -la /usr/lib64/php/modules/ | grep mongo \
 && php -m | grep mongo

ADD ./environment/mongodb/mongodb.repo /etc/yum.repos.d/mongodb.repo
RUN yum -y install mongo-10gen mongo-10gen-server

WORKDIR /shared/site

# add startup shell scripts
ADD ./environment/init.sh /tmp/init.sh

CMD ["/bin/bash", "/tmp/init.sh"]