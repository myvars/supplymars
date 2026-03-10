#!/usr/bin/env bash
# playground-reset.sh — Resets playground database and S3 from live data.
# Runs inside the playground reset container (not on the host).

set -euo pipefail

# Map project AWS env vars to standard AWS CLI env vars
export AWS_ACCESS_KEY_ID="${AWS_S3_ACCESS_ID}"
export AWS_SECRET_ACCESS_KEY="${AWS_S3_SECRET_ACCESS_KEY}"
export AWS_DEFAULT_REGION="${AWS_S3_REGION}"

echo "$(date '+%Y-%m-%d %H:%M:%S') Starting playground reset..."

# 1. Restore database from backup file (produced by live stack's app:backup-database --local-copy)
BACKUP_FILE="/backups/latest.sql.gz"
if [ ! -f "$BACKUP_FILE" ]; then
    echo "ERROR: Backup file not found at $BACKUP_FILE"
    exit 1
fi
echo "Restoring database from $BACKUP_FILE..."
gunzip < "$BACKUP_FILE" | mysql -h database -P 3306 \
    -u root -p"${MYSQL_ROOT_PASSWORD}" \
    --skip-ssl app

# 2. Sync S3 uploads and media from live (root) to playground prefix
echo "Syncing S3 uploads..."
aws s3 sync "s3://${AWS_S3_BUCKET}/uploads/" "s3://${AWS_S3_BUCKET}/${AWS_S3_PRODUCTS_PREFIX}/uploads/" --delete --acl public-read
echo "Syncing S3 media..."
aws s3 sync "s3://${AWS_S3_BUCKET}/media/" "s3://${AWS_S3_BUCKET}/${AWS_S3_PRODUCTS_PREFIX}/media/" --delete --acl public-read

# 3. Flush playground Redis
echo "Flushing playground Redis..."
redis-cli -h redis -a "${REDIS_PASSWORD}" FLUSHALL

# 4. Purge playground RabbitMQ queue
echo "Purging playground RabbitMQ queue..."
curl -sf -X DELETE \
    -u "app:${RABBITMQ_PASSWORD}" \
    "http://rabbitmq:15672/api/queues/%2f/messages/contents" || true

echo "$(date '+%Y-%m-%d %H:%M:%S') Playground reset complete."
