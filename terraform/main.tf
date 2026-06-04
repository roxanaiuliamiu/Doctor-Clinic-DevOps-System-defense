resource "local_file" "deployment_summary" {
  filename = "${path.module}/generated-deployment-summary.txt"

  content = <<EOT
Project: ${var.project_name}
Environment: ${var.environment}
Application Port: ${var.app_port}
Database Name: ${var.db_name}
Database User: ${var.db_user}

Infrastructure Summary:
- Docker Compose environment available
- Kubernetes manifests prepared
- GitHub Actions CI configured
- Backup script available
- Monitoring plan documented
EOT
}
