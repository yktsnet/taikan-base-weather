terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
  required_version = ">= 1.5.0"
}

provider "aws" {
  region = var.aws_region

  # Use localstack credentials and skip validation if use_localstack is true
  access_key                  = var.use_localstack ? "test" : null
  secret_key                  = var.use_localstack ? "test" : null
  skip_credentials_validation = var.use_localstack
  skip_metadata_api_check     = var.use_localstack
  skip_requesting_account_id  = var.use_localstack
  s3_use_path_style           = var.use_localstack

  endpoints {
    s3  = var.use_localstack ? "http://localhost:4566" : null
    sqs = var.use_localstack ? "http://localhost:4566" : null
    iam = var.use_localstack ? "http://localhost:4566" : null
  }

  default_tags {
    tags = {
      Project     = "kawa-watch"
      Environment = var.environment
      ManagedBy   = "Terraform"
    }
  }
}
