FROM centos:centos7
MAINTAINER motikan2010

RUN rm /bin/sh && ln -s /bin/bash /bin/sh

RUN yum -y install wget epel-release

RUN wget http://rpms.famillecollet.com/enterprise/remi-release-7.rpm
RUN rpm -Uvh ./remi-release-7.rpm
RUN sed -i '4a priority=1' /etc/yum.repos.d/remi-php71.repo
RUN yum -y install --enablerepo=remi-php71 php php-fpm php-mcrypt php-cli php-common php-devel php-gd php-mbstring php-mysqlnd php-opcache php-pdo php-pear php-pecl-apcu php-pecl-zip php-process php-xml
RUN yum -y install mysql
RUN yum clean all

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir /var/www/app
COPY . /var/www/app
ENTRYPOINT sh /var/www/app/startup.sh db docker docker
