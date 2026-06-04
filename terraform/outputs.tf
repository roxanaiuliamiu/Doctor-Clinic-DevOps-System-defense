output "project_name" {
  value = var.project_name
}

output "environment" {
  value = var.environment
}

output "deployment_summary_file" {
  value = local_file.deployment_summary.filename
}
