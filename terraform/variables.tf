variable "project_name" {
  description = "Project name"
  type        = string
  default     = "Doctor-Clinic-DevOps-System"
}

variable "environment" {
  description = "Deployment environment"
  type        = string
  default     = "staging"
}

variable "app_port" {
  description = "Application exposed port"
  type        = number
  default     = 8080
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "doktors"
}

variable "db_user" {
  description = "Database username"
  type        = string
  default     = "doktors_user"
}
