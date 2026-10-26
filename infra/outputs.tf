output "server_public_ip" {
  description = "Dirección IP pública estática (Elastic IP) asignada al servidor"
  value       = aws_eip.server_eip.public_ip
}

output "server_instance_id" {
  description = "Identificador de la instancia de cómputo en la nube"
  value       = aws_instance.server.id
}

output "s3_backup_bucket_name" {
  description = "Nombre único del bucket S3 configurado para respaldos y comprobantes"
  value       = aws_s3_bucket.backups.id
}

output "s3_backup_bucket_arn" {
  description = "ARN del bucket S3 de almacenamiento y respaldos"
  value       = aws_s3_bucket.backups.arn
}

output "ec2_iam_role_arn" {
  description = "ARN del rol IAM asignado al servidor (mínimo privilegio S3/SSM/SES)"
  value       = aws_iam_role.ec2_role.arn
}

output "github_actions_role_arn" {
  description = "ARN del rol OIDC para despliegue automatizado desde GitHub Actions"
  value       = aws_iam_role.github_actions_oidc.arn
}

output "ssm_parameter_prefix" {
  description = "Ruta prefijo en AWS Systems Manager Parameter Store para secretos"
  value       = "/getsemani/"
}
