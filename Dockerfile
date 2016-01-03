FROM centos:6
MAINTAINER itspoma <itspoma@gmail.com>

RUN true \
    && yum clean all \
    && yum install -y git curl mc

# nodejs
RUN true \
    && curl --silent --location https://rpm.nodesource.com/setup | bash - \
    && yum -y install nodejs \
    && npm -g install npm@latest \
    && yum -y install gcc-c++ make

# ruby
RUN true \
    && yum -y install ruby ruby-devel rubygems \
    && gem install --no-rdoc --no-ri sass

# bower
RUN true \
    && npm install -g bower \
    && npm install -g grunt-cli \
    && npm install -g http-server

WORKDIR /shared/site