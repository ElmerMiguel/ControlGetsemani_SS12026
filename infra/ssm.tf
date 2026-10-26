# ==============================================================================
# ssm.tf — Parámetros Seguros en AWS Systems Manager Parameter Store
#
# REGLA DE ORO DE SEGURIDAD (ARQUITECTURA_FASE3 §5.2 y §10.2):
# NINGÚN secreto real se escribe en código, repositorios Git ni archivos tfvars.
#
# Los parámetros se inicializan con un valor placeholder ("CAMBIAR-A-MANO-EN-CONSOLA").
# Los valores reales de producción (contraseñas de BD, APP_KEY de Laravel, llaves SMTP)
# son inyectados directamente por el desarrollador en AWS mediante la consola web
# o con el comando seguro:
#
#   aws ssm put-parameter --name "/getsemani/db_password" --value "TU_PASS_REAL" --type "SecureString" --overwrite
#
# La directiva lifecycle.ignore_changes previene que ejecuciones futuras de Terraform
# sobreescriban los secretos reales configurados en la nube.
# ==============================================================================

resource "aws_ssm_parameter" "db_password" {
  name        = "/getsemani/db_password"
  description = "Contraseña de producción para el usuario de base de datos MariaDB"
  type        = "SecureString"
  value       = "CAMBIAR-A-MANO-EN-CONSOLA"

  lifecycle {
    ignore_changes = [value]
  }

  tags = {
    Name = "getsemani-ssm-db-password"
  }
}

resource "aws_ssm_parameter" "app_key" {
  name        = "/getsemani/app_key"
  description = "Clave de cifrado de aplicación Laravel (APP_KEY) en producción"
  type        = "SecureString"
  value       = "CAMBIAR-A-MANO-EN-CONSOLA"

  lifecycle {
    ignore_changes = [value]
  }

  tags = {
    Name = "getsemani-ssm-app-key"
  }
}

resource "aws_ssm_parameter" "mail_password" {
  name        = "/getsemani/mail_password"
  description = "Contraseña o credencial SMTP para despacho de correos vía Amazon SES"
  type        = "SecureString"
  value       = "CAMBIAR-A-MANO-EN-CONSOLA"

  lifecycle {
    ignore_changes = [value]
  }

  tags = {
    Name = "getsemani-ssm-mail-password"
  }
}
