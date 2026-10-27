variable "aws_region" {
  description = "Región de AWS donde se despliega la infraestructura"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Ambiente de despliegue (production / staging)"
  type        = string
  default     = "production"
}

variable "vpc_cidr" {
  description = "Bloque CIDR principal para la VPC"
  type        = string
  default     = "10.0.0.0/16"
}

variable "public_subnet_cidr" {
  description = "Bloque CIDR para la subred pública"
  type        = string
  default     = "10.0.1.0/24"
}

variable "instance_type" {
  description = "Tipo de instancia de cómputo para el servidor VPS"
  type        = string
  default     = "t3.micro"
}

variable "ip_administrador" {
  description = "Dirección IP pública del administrador en formato CIDR (/32) para acceso SSH exclusivo (puerto 22)"
  type        = string
  default     = "190.104.120.15/32"
}

variable "ssh_key_name" {
  description = "Nombre del Key Pair SSH existente para acceder a la instancia"
  type        = string
  default     = "getsemani-deploy-key"
}

variable "github_repo" {
  description = "Repositorio de GitHub autorizado para asumir el rol OIDC (ej. ElmerMiguel/ControlGetsemani_SS12026)"
  type        = string
  default     = "ElmerMiguel/ControlGetsemani_SS12026"
}

variable "domain_name" {
  description = "Nombre de dominio público para la aplicación web Getsemaní"
  type        = string
  default     = "finanzas.getsemani.gt"
}
