# ==============================================================================
# compute.tf — Instancia de Cómputo (Servidor VPS / EC2, Disco y Elastic IP)
#
# Aprovisiona la máquina virtual con Ubuntu Linux oficial, asigna una dirección IP
# pública estática (Elastic IP) para el dominio, cifra el almacenamiento en reposo
# con KMS y ejecuta un script de inicio (cloud-init) para tener Docker listo.
# ==============================================================================

data "aws_ami" "ubuntu" {
  most_recent = true
  owners      = ["099720109477"] # Canonical (Ubuntu oficial)

  filter {
    name   = "name"
    values = ["ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*"]
  }

  filter {
    name   = "virtualization-type"
    values = ["hvm"]
  }
}

resource "aws_instance" "server" {
  ami                  = data.aws_ami.ubuntu.id
  instance_type        = var.instance_type
  subnet_id            = aws_subnet.public.id
  vpc_security_group_ids = [aws_security_group.web_sg.id]
  key_name             = var.ssh_key_name
  iam_instance_profile = aws_iam_instance_profile.ec2_profile.name

  root_block_device {
    volume_size           = 25 # GB suficientes para sistema operativo, contenedores y base de datos
    volume_type           = "gp3"
    encrypted             = true # Cifrado en reposo obligatorio por rúbrica de seguridad
    delete_on_termination = true

    tags = {
      Name = "getsemani-server-root-${var.environment}"
    }
  }

  # Script de inicialización automatizada en primer arranque (cloud-init)
  user_data = <<-EOF
              #!/bin/bash
              set -e

              # 1. Actualización básica del sistema
              apt-get update -y
              apt-get upgrade -y
              apt-get install -y ca-certificates curl gnupg lsb-release git unzip awscli

              # 2. Instalación oficial de Docker Engine y Docker Compose Plugin
              install -m 0755 -d /etc/apt/keyrings
              curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
              chmod a+r /etc/apt/keyrings/docker.asc

              echo \
                "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu \
                $(. /etc/os-release && echo "$VERSION_CODENAME") stable" | \
                tee /etc/apt/sources.list.d/docker.list > /dev/null

              apt-get update -y
              apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

              # 3. Habilitar servicio y agregar usuario ubuntu al grupo docker
              systemctl enable docker
              systemctl start docker
              usermod -aG docker ubuntu

              # 4. Crear directorio de trabajo para el despliegue
              mkdir -p /home/ubuntu/getsemani-app
              chown -R ubuntu:ubuntu /home/ubuntu/getsemani-app

              echo "Instalación completada con éxito: Docker $(docker --version)" > /var/log/getsemani-init.log
              EOF

  tags = {
    Name = "getsemani-server-${var.environment}"
  }
}

# Elastic IP (Dirección IP pública estática para vinculación DNS inmutable)
resource "aws_eip" "server_eip" {
  instance = aws_instance.server.id
  domain   = "vpc"

  tags = {
    Name = "getsemani-server-eip-${var.environment}"
  }
}
