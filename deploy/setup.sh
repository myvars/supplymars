#!/bin/bash
# Get login credentials
read -p $'Domain Name:\n' domainvar
read -sp $'Symfony Secret:\n' symfonysecretvar
read -sp $'Database Password:\n' dbpassvar
read -sp $'Mailer Key:\n' mailerkeypairvar
read -p $'AWS S3 Bucket:\n' s3bucketvar
read -sp $'AWS S3 ID:\n' s3accessidvar
read -sp $'AWS S3 Secret:\n' s3accesssecretvar
read -p $'Dev Email:\n' devemailvar

# Install yarn $ supervisor
sudo apt update && sudo apt upgrade -y
sudo apt install -y supervisor

# Install symfony cli
wget https://get.symfony.com/cli/installer -O - | bash
sudo mv /home/bitnami/.symfony5/bin/symfony /usr/local/bin/symfony

# Copy apache vhosts
cp /opt/bitnami/projects/app/deploy/app-vhost.conf /opt/bitnami/apache2/conf/vhosts/app-vhost.conf
cp /opt/bitnami/projects/app/deploy/app-https-vhost.conf /opt/bitnami/apache2/conf/vhosts/app-https-vhost.conf

# Copy logos/icons/favicons
mkdir -p /opt/bitnami/projects/app/public/images/icons
cp /opt/bitnami/projects/app/assets/images/icons/${domainvar}/* /opt/bitnami/projects/app/public/images/icons
cp /opt/bitnami/projects/app/templates/logo/${domainvar}/* /opt/bitnami/projects/app/templates/logo

# set Production Environment vars
echo "SITE_DOMAIN=${domainvar}" >> /opt/bitnami/projects/app/.env
echo "SITE_BASE_HOST=www.${domainvar}" >> /opt/bitnami/projects/app/.env
echo "APP_SECRET=${symfonysecretvar}" >> /opt/bitnami/projects/app/.env
echo "MAILER_DSN=ses+smtp://${mailerkeypairvar}@default?region=eu-west-2" >> /opt/bitnami/projects/app/.env
echo "DATABASE_URL=\"mysql://root:${dbpassvar}@127.0.0.1:3306/app?serverVersion=8&charset=utf8mb4\"" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_BUCKET=${s3bucketvar}" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_ACCESS_ID=${s3accessidvar}" >> /opt/bitnami/projects/app/.env
echo "AWS_S3_SECRET_ACCESS_KEY=${s3accesssecretvar}" >> /opt/bitnami/projects/app/.env
echo "DEV_MAIL_RECIPIENT=${devemailvar}" >> /opt/bitnami/projects/app/.env