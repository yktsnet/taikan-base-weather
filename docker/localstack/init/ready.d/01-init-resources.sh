#!/bin/bash
set -euo pipefail

REGION="ap-northeast-1"
ACCOUNT="000000000000"
MAX_RECEIVE_COUNT=5

echo "=== LocalStack init: creating S3 bucket and SQS queues ==="

# --- S3 bucket (created first so SQS errors don't block it) ---
BUCKET_NAME="kawa-watch-bucket"
echo "Creating S3 bucket: ${BUCKET_NAME}"
awslocal s3 mb "s3://${BUCKET_NAME}" --region "${REGION}" || true

# --- DLQ ---
DLQ_NAME="kawa-watch-raw-events-dlq"
echo "Creating DLQ: ${DLQ_NAME}"
awslocal sqs create-queue --queue-name "${DLQ_NAME}" --region "${REGION}"

DLQ_ARN=$(awslocal sqs get-queue-attributes \
  --queue-url "http://localhost:4566/${ACCOUNT}/${DLQ_NAME}" \
  --attribute-names QueueArn \
  --region "${REGION}" \
  --query 'Attributes.QueueArn' \
  --output text)
echo "DLQ ARN: ${DLQ_ARN}"

# --- Main queues with RedrivePolicy ---
# RedrivePolicy value is JSON, double-escaped for embedding in the --attributes JSON parameter
REDRIVE_POLICY="{\\\"deadLetterTargetArn\\\":\\\"${DLQ_ARN}\\\",\\\"maxReceiveCount\\\":\\\"${MAX_RECEIVE_COUNT}\\\"}"

for QUEUE_NAME in kawa-watch-water-level kawa-watch-weather; do
  echo "Creating queue: ${QUEUE_NAME} (RedrivePolicy → ${DLQ_NAME})"
  awslocal sqs create-queue \
    --queue-name "${QUEUE_NAME}" \
    --attributes "{\"RedrivePolicy\":\"${REDRIVE_POLICY}\"}" \
    --region "${REGION}"
done

echo "=== LocalStack init: done ==="
