# ==============================================================================
# iam.tf — Roles y Políticas de Seguridad IAM (Principio de Mínimo Privilegio)
#
# Cumple con ARQUITECTURA_FASE3 §5.3 y §5.4:
# 1. Rol para la instancia EC2: Permite leer parámetros en SSM, subir backups a S3
#    y enviar correos vía SES, sin requerir credenciales ni llaves estáticas en disco.
# 2. Rol OIDC para GitHub Actions: Permite autenticar el pipeline CI/CD sin almacenar
#    llaves de acceso permanente en GitHub (prevención de fugas de credenciales).
# ==============================================================================

# ------------------------------------------------------------------------------
# 1. ROL IAM PARA LA INSTANCIA DE CÓMPUTO (EC2)
# ------------------------------------------------------------------------------

data "aws_iam_policy_document" "ec2_trust" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["ec2.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "ec2_role" {
  name               = "getsemani-ec2-role-${var.environment}"
  assume_role_policy = data.aws_iam_policy_document.ec2_trust.json

  tags = {
    Name = "getsemani-ec2-role-${var.environment}"
  }
}

data "aws_iam_policy_document" "ec2_permissions" {
  # Permiso S3: solo lectura/escritura en el bucket del proyecto
  statement {
    sid    = "S3BackupAccess"
    effect = "Allow"
    actions = [
      "s3:PutObject",
      "s3:GetObject",
      "s3:ListBucket"
    ]
    resources = [
      aws_s3_bucket.backups.arn,
      "${aws_s3_bucket.backups.arn}/*"
    ]
  }

  # Permiso SSM: solo lectura de secretos bajo el prefijo /getsemani/
  statement {
    sid    = "SSMParametersAccess"
    effect = "Allow"
    actions = [
      "ssm:GetParameter",
      "ssm:GetParametersByPath"
    ]
    resources = [
      "arn:aws:ssm:${var.aws_region}:${data.aws_caller_identity.current.account_id}:parameter/getsemani/*"
    ]
  }

  # Permiso SES: envío de correos de 2FA y recuperación de cuenta
  statement {
    sid    = "SESSendEmail"
    effect = "Allow"
    actions = [
      "ses:SendEmail",
      "ses:SendRawEmail"
    ]
    resources = ["*"]
  }

  # Permiso CloudWatch: métricas de estado
  statement {
    sid    = "CloudWatchMetrics"
    effect = "Allow"
    actions = [
      "cloudwatch:PutMetricData"
    ]
    resources = ["*"]
  }
}

resource "aws_iam_policy" "ec2_policy" {
  name        = "getsemani-ec2-policy-${var.environment}"
  description = "Politica de minimo privilegio para servidor de finanzas Getsemani"
  policy      = data.aws_iam_policy_document.ec2_permissions.json
}

resource "aws_iam_role_policy_attachment" "ec2_attach" {
  role       = aws_iam_role.ec2_role.name
  policy_arn = aws_iam_policy.ec2_policy.arn
}

resource "aws_iam_instance_profile" "ec2_profile" {
  name = "getsemani-ec2-profile-${var.environment}"
  role = aws_iam_role.ec2_role.name
}

# ------------------------------------------------------------------------------
# 2. ROL OIDC PARA GITHUB ACTIONS (Despliegue sin llaves de acceso en el repo)
# ------------------------------------------------------------------------------

data "aws_iam_policy_document" "github_oidc_trust" {
  statement {
    effect  = "Allow"
    actions = ["sts:AssumeRoleWithWebIdentity"]

    principals {
      type        = "Federated"
      identifiers = ["arn:aws:iam::${data.aws_caller_identity.current.account_id}:oidc-provider/token.actions.githubusercontent.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "token.actions.githubusercontent.com:aud"
      values   = ["sts.amazonaws.com"]
    }

    condition {
      test     = "StringLike"
      variable = "token.actions.githubusercontent.com:sub"
      values   = ["repo:${var.github_repo}:*"]
    }
  }
}

resource "aws_iam_role" "github_actions_oidc" {
  name               = "getsemani-github-oidc-role-${var.environment}"
  assume_role_policy = data.aws_iam_policy_document.github_oidc_trust.json

  tags = {
    Name = "getsemani-github-oidc-role-${var.environment}"
  }
}

data "aws_iam_policy_document" "github_actions_permissions" {
  statement {
    sid    = "EC2DescribeForDeploy"
    effect = "Allow"
    actions = [
      "ec2:DescribeInstances"
    ]
    resources = ["*"]
  }
}

resource "aws_iam_policy" "github_actions_policy" {
  name        = "getsemani-github-actions-policy-${var.environment}"
  description = "Permisos minimos para verificar IP de servidor en pipeline de GitHub Actions"
  policy      = data.aws_iam_policy_document.github_actions_permissions.json
}

resource "aws_iam_role_policy_attachment" "github_actions_attach" {
  role       = aws_iam_role.github_actions_oidc.name
  policy_arn = aws_iam_policy.github_actions_policy.arn
}
