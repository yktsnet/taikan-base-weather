#!/bin/bash
set -euo pipefail

REGION="ap-northeast-1"
ACCOUNT="000000000000"
MAX_RECEIVE_COUNT=5

echo "=== LocalStack init: creating SQS queues and S3 bucket ==="

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
REDRIVE_POLICY="{\"deadLetterTargetArn\":\"${DLQ_ARN}\",\"maxReceiveCount\":\"${MAX_RECEIVE_COUNT}\"}"

for QUEUE_NAME in kawa-watch-water-level kawa-watch-weather; do
  echo "Creating queue: ${QUEUE_NAME} (RedrivePolicy → ${DLQ_NAME})"
  awslocal sqs create-queue \
    --queue-name "${QUEUE_NAME}" \
    --attributes "RedrivePolicy=${REDRIVE_POLICY}" \
    --region "${REGION}"
done

# --- S3 bucket ---
BUCKET_NAME="kawa-watch-bucket"
echo "Creating S3 bucket: ${BUCKET_NAME}"
awslocal s3 mb "s3://${BUCKET_NAME}" --region "${REGION}" || true

echo "=== LocalStack init: done ==="
