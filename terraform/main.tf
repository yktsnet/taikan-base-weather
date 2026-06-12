# ==========================================
# Network (VPC & Subnets)
# ==========================================
resource "aws_vpc" "main" {
  count                = var.use_localstack ? 0 : 1
  cidr_block           = "10.0.0.0/16"
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = {
    Name = "kawa-watch-vpc-${var.environment}"
  }
}

# Internet Gateway
resource "aws_internet_gateway" "main" {
  count  = var.use_localstack ? 0 : 1
  vpc_id = aws_vpc.main[0].id

  tags = {
    Name = "kawa-watch-igw-${var.environment}"
  }
}

# Public Subnets
resource "aws_subnet" "public_1a" {
  count                   = var.use_localstack ? 0 : 1
  vpc_id                  = aws_vpc.main[0].id
  cidr_block              = "10.0.1.0/24"
  availability_zone       = "ap-northeast-1a"
  map_public_ip_on_launch = true

  tags = {
    Name = "kawa-watch-public-1a-${var.environment}"
  }
}

resource "aws_subnet" "public_1c" {
  count                   = var.use_localstack ? 0 : 1
  vpc_id                  = aws_vpc.main[0].id
  cidr_block              = "10.0.2.0/24"
  availability_zone       = "ap-northeast-1c"
  map_public_ip_on_launch = true

  tags = {
    Name = "kawa-watch-public-1c-${var.environment}"
  }
}

# Private Subnets (For ECS)
resource "aws_subnet" "private_1a" {
  count             = var.use_localstack ? 0 : 1
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = "10.0.10.0/24"
  availability_zone = "ap-northeast-1a"

  tags = {
    Name = "kawa-watch-private-1a-${var.environment}"
  }
}

resource "aws_subnet" "private_1c" {
  count             = var.use_localstack ? 0 : 1
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = "10.0.11.0/24"
  availability_zone = "ap-northeast-1c"

  tags = {
    Name = "kawa-watch-private-1c-${var.environment}"
  }
}

# Isolated Subnets (For RDS)
resource "aws_subnet" "isolated_1a" {
  count             = var.use_localstack ? 0 : 1
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = "10.0.20.0/24"
  availability_zone = "ap-northeast-1a"

  tags = {
    Name = "kawa-watch-isolated-1a-${var.environment}"
  }
}

resource "aws_subnet" "isolated_1c" {
  count             = var.use_localstack ? 0 : 1
  vpc_id            = aws_vpc.main[0].id
  cidr_block        = "10.0.21.0/24"
  availability_zone = "ap-northeast-1c"

  tags = {
    Name = "kawa-watch-isolated-1c-${var.environment}"
  }
}

# Public Route Table
resource "aws_route_table" "public" {
  count  = var.use_localstack ? 0 : 1
  vpc_id = aws_vpc.main[0].id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.main[0].id
  }

  tags = {
    Name = "kawa-watch-public-rt-${var.environment}"
  }
}

resource "aws_route_table_association" "public_1a" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.public_1a[0].id
  route_table_id = aws_route_table.public[0].id
}

resource "aws_route_table_association" "public_1c" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.public_1c[0].id
  route_table_id = aws_route_table.public[0].id
}

# Private/Isolated Route Tables (No external access by default in this phase)
resource "aws_route_table" "private" {
  count  = var.use_localstack ? 0 : 1
  vpc_id = aws_vpc.main[0].id

  tags = {
    Name = "kawa-watch-private-rt-${var.environment}"
  }
}

resource "aws_route_table_association" "private_1a" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.private_1a[0].id
  route_table_id = aws_route_table.private[0].id
}

resource "aws_route_table_association" "private_1c" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.private_1c[0].id
  route_table_id = aws_route_table.private[0].id
}

resource "aws_route_table" "isolated" {
  count  = var.use_localstack ? 0 : 1
  vpc_id = aws_vpc.main[0].id

  tags = {
    Name = "kawa-watch-isolated-rt-${var.environment}"
  }
}

resource "aws_route_table_association" "isolated_1a" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.isolated_1a[0].id
  route_table_id = aws_route_table.isolated[0].id
}

resource "aws_route_table_association" "isolated_1c" {
  count          = var.use_localstack ? 0 : 1
  subnet_id      = aws_subnet.isolated_1c[0].id
  route_table_id = aws_route_table.isolated[0].id
}

# ==========================================
# Security Groups
# ==========================================

# Placeholder SG for ECS
resource "aws_security_group" "ecs_app" {
  count       = var.use_localstack ? 0 : 1
  name        = "kawa-watch-ecs-app-sg-${var.environment}"
  description = "Security Group for ECS App/Worker"
  vpc_id      = aws_vpc.main[0].id

  # Outbound access to internet (assuming NAT Gateway will be added later)
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "kawa-watch-ecs-app-sg-${var.environment}"
  }
}

# RDS Security Group
resource "aws_security_group" "rds" {
  count       = var.use_localstack ? 0 : 1
  name        = "kawa-watch-rds-sg-${var.environment}"
  description = "Security Group for RDS"
  vpc_id      = aws_vpc.main[0].id

  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    security_groups = [aws_security_group.ecs_app[0].id]
  }

  tags = {
    Name = "kawa-watch-rds-sg-${var.environment}"
  }
}


# ==========================================
# Database (RDS MySQL)
# ==========================================
resource "aws_db_subnet_group" "rds" {
  count      = var.use_localstack ? 0 : 1
  name       = "kawa-watch-db-subnet-group-${var.environment}"
  subnet_ids = [aws_subnet.isolated_1a[0].id, aws_subnet.isolated_1c[0].id]

  tags = {
    Name = "kawa-watch-db-subnet-group-${var.environment}"
  }
}

resource "aws_db_instance" "main" {
  count                 = var.use_localstack ? 0 : 1
  identifier            = "kawa-watch-db"
  engine                = "mysql"
  engine_version        = "8.0"
  instance_class        = "db.t4g.micro"
  allocated_storage     = 20
  max_allocated_storage = 100 # Enable autoscaling
  storage_type          = "gp3"

  username = var.db_username
  password = var.db_password

  db_subnet_group_name   = aws_db_subnet_group.rds[0].name
  vpc_security_group_ids = [aws_security_group.rds[0].id]

  multi_az            = false
  publicly_accessible = false
  skip_final_snapshot = true # For development/cost-saving. Set to false for prod

  tags = {
    Name = "kawa-watch-db-${var.environment}"
  }
}

# ==========================================
# Queue (SQS)
# ==========================================
resource "aws_sqs_queue" "dlq" {
  name = "kawa-watch-raw-events-dlq"
}

resource "aws_sqs_queue" "main" {
  name = "kawa-watch-raw-events"

  redrive_policy = jsonencode({
    deadLetterTargetArn = aws_sqs_queue.dlq.arn
    maxReceiveCount     = 3
  })
}

# ==========================================
# Storage (S3)
# ==========================================
resource "aws_s3_bucket" "csv_archive" {
  bucket = "kawa-watch-csv-archive-${var.environment}"
}

resource "aws_s3_bucket_public_access_block" "csv_archive" {
  bucket = aws_s3_bucket.csv_archive.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}
