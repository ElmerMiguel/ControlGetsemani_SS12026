# ==============================================================================
# storage.tf — Almacenamiento en la Nube (Bucket S3 Cifrado con Versionado)
#
# Almacena los respaldos automatizados de base de datos (.sql.gz) y comprobantes
# emitidos. Cumple estrictamente con:
# - Bloqueo total de acceso público (Block Public Access activado).
# - Cifrado del lado del servidor SSE-S3 (AES256).
# - Versionado de objetos para protección ante borrados accidentales o ransomware.
# - Ciclo de vida: transición a almacenamiento Glacier a los 90 días para ahorro.
# ==============================================================================

resource "aws_s3_bucket" "backups" {
  bucket        = "getsemani-backups-${var.environment}-${data.aws_caller_identity.current.account_id}"
  force_destroy = false

  tags = {
    Name = "getsemani-backups-${var.environment}"
  }
}

# 1. Versionado de archivos
resource "aws_s3_bucket_versioning" "versioning" {
  bucket = aws_s3_bucket.backups.id

  versioning_configuration {
    status = "Enabled"
  }
}

# 2. Cifrado en reposo obligatorio por defecto (SSE-S3)
resource "aws_s3_bucket_server_side_encryption_configuration" "encryption" {
  bucket = aws_s3_bucket.backups.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# 3. Bloqueo estricto de todo acceso público
resource "aws_s3_bucket_public_access_block" "public_block" {
  bucket = aws_s3_bucket.backups.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

# 4. Regla de ciclo de vida: mover copias completas históricas a Glacier tras 90 días
resource "aws_s3_bucket_lifecycle_configuration" "lifecycle" {
  bucket = aws_s3_bucket.backups.id

  rule {
    id     = "archivar-respaldos-glacier-90dias"
    status = "Enabled"

    filter {
      prefix = "full/"
    }

    transition {
      days          = 90
      storage_class = "GLACIER"
    }

    noncurrent_version_expiration {
      noncurrent_days = 180
    }
  }
}
