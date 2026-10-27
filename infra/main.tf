terraform {
  required_version = ">= 1.5.0"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }

  # Backend de estado: por defecto local para facilidad de desarrollo.
  # Para producción con equipo o CI/CD estricto, descomentar el backend en S3:
  # backend "s3" {
  #   bucket         = "getsemani-terraform-state"
  #   key            = "prod/terraform.tfstate"
  #   region         = "us-east-1"
  #   dynamodb_table = "getsemani-terraform-locks"
  #   encrypt        = true
  # }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project   = "getsemani-finanzas"
      Phase     = "3"
      ManagedBy = "Terraform"
      Owner     = "Elmer Wilson"
    }
  }
}

data "aws_caller_identity" "current" {}
