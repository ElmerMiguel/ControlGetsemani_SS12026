# ==============================================================================
# security.tf — Grupos de Seguridad (Firewall a nivel de red)
#
# Regla de oro: El puerto 22 NUNCA se abre al mundo (0.0.0.0/0). Se restringe
# exclusivamente a la IP fija o rango CIDR del administrador (var.ip_administrador).
# Los puertos 80 y 443 se abren para el tráfico web legítimo HTTP/HTTPS.
# ==============================================================================

resource "aws_security_group" "web_sg" {
  name        = "getsemani-web-sg-${var.environment}"
  description = "Reglas de firewall para el servidor web y administrativo Getsemani"
  vpc_id      = aws_vpc.main.id

  # Acceso SSH restringido exclusivamente a la IP del desarrollador/administrador
  ingress {
    description = "SSH seguro restringido al administrador"
    from_port   = 22
    to_port     = 22
    protocol    = "tcp"
    cidr_blocks = [var.ip_administrador]
  }

  # Tráfico web HTTP público (redirección a HTTPS en Nginx)
  ingress {
    description = "HTTP trafico publico para Let's Encrypt y redireccion"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Tráfico web HTTPS público seguro
  ingress {
    description = "HTTPS trafico publico cifrado con TLS"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  # Salida libre para actualizaciones de paquetes, llamadas a SSM y SES
  egress {
    description = "Permitir todo el trafico de salida saliente"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "getsemani-web-sg-${var.environment}"
  }
}
