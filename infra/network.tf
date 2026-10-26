# ==============================================================================
# network.tf — Red Virtual (VPC, Subred, Puerta de Enlace y Tabla de Enrutamiento)
#
# DECISIÓN ARQUITECTÓNICA D3-08:
# Para este proyecto académico (con presupuesto límite de $200 de crédito gratuito),
# se implementa una VPC con una única subred pública conectada a Internet Gateway.
#
# ¿Por qué NO hay subred privada con NAT Gateway?
# Un NAT Gateway administrado en AWS tiene un costo fijo de ~$32/mes sin contar
# transferencia de datos, lo que consumiría casi el 50% del presupuesto disponible
# en solo dos meses.
#
# Mitigación de seguridad implementada:
# 1. El Security Group (security.tf) restringe el tráfico entrante de manera estricta:
#    únicamente los puertos web 80 y 443 están abiertos al público general.
# 2. El puerto administrativo 22 (SSH) está restringido con máscara /32 a la IP
#    específica del administrador.
# 3. El motor de base de datos MariaDB corre dentro de un contenedor Docker aislado
#    en la red interna docker-bridge; el puerto 3306 NUNCA se expone hacia el host ni a internet.
# ==============================================================================

resource "aws_vpc" "main" {
  cidr_block           = var.vpc_cidr
  enable_dns_support   = true
  enable_dns_hostnames = true

  tags = {
    Name = "getsemani-vpc-${var.environment}"
  }
}

resource "aws_internet_gateway" "gw" {
  vpc_id = aws_vpc.main.id

  tags = {
    Name = "getsemani-igw-${var.environment}"
  }
}

resource "aws_subnet" "public" {
  vpc_id                  = aws_vpc.main.id
  cidr_block              = var.public_subnet_cidr
  availability_zone       = "${var.aws_region}a"
  map_public_ip_on_launch = true

  tags = {
    Name = "getsemani-public-subnet-${var.environment}"
  }
}

resource "aws_route_table" "public" {
  vpc_id = aws_vpc.main.id

  route {
    cidr_block = "0.0.0.0/0"
    gateway_id = aws_internet_gateway.gw.id
  }

  tags = {
    Name = "getsemani-public-rt-${var.environment}"
  }
}

resource "aws_route_table_association" "public" {
  subnet_id      = aws_subnet.public.id
  route_table_id = aws_route_table.public.id
}
