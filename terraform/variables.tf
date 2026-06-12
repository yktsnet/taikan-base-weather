variable "environment" {
  description = "The environment for deployment (e.g., dev, stg, prod)"
  type        = string
  default     = "dev"
}

variable "aws_region" {
  description = "The AWS region to deploy to"
  type        = string
  default     = "ap-northeast-1"
}

variable "use_localstack" {
  description = "Whether to use LocalStack for local development"
  type        = bool
  default     = true
}

variable "db_username" {
  description = "The master username for the RDS instance"
  type        = string
  default     = "admin"
  sensitive   = true
}

variable "db_password" {
  description = "The master password for the RDS instance"
  type        = string
  default     = "ChangeMe123!" # Placeholder, should be injected via CI/CD or Secrets Manager
  sensitive   = true
}
