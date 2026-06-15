output "vpc_id" {
  description = "The ID of the VPC"
  value       = one(aws_vpc.main[*].id)
}

output "public_subnet_ids" {
  description = "The IDs of the public subnets"
  value       = concat(aws_subnet.public_1a[*].id, aws_subnet.public_1c[*].id)
}

output "private_subnet_ids" {
  description = "The IDs of the private subnets"
  value       = concat(aws_subnet.private_1a[*].id, aws_subnet.private_1c[*].id)
}

output "isolated_subnet_ids" {
  description = "The IDs of the isolated subnets"
  value       = concat(aws_subnet.isolated_1a[*].id, aws_subnet.isolated_1c[*].id)
}

output "rds_endpoint" {
  description = "The endpoint for the RDS instance"
  value       = one(aws_db_instance.main[*].endpoint)
}

output "sqs_main_queue_url" {
  description = "The URL of the main SQS queue"
  value       = aws_sqs_queue.main.url
}

output "sqs_dlq_url" {
  description = "The URL of the Dead Letter Queue"
  value       = aws_sqs_queue.dlq.url
}

output "s3_csv_archive_bucket" {
  description = "The name of the S3 bucket for CSV archives"
  value       = aws_s3_bucket.csv_archive.bucket
}

output "ecs_app_sg_id" {
  description = "The ID of the Security Group for ECS App/Worker"
  value       = one(aws_security_group.ecs_app[*].id)
}
