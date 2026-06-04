# Architecture Overview

## High-Level Architecture

The Doctor Clinic DevOps System is structured into the following layers:

1. User Layer
2. Web Layer
3. Application Layer
4. Database Layer
5. CI/CD Layer
6. Deployment Layer
7. Operations Layer

## 1. User Layer
Users access the platform through a browser:
- Patient
- Doctor
- Admin

## 2. Web Layer
Requests are received through Nginx.
Nginx serves as the reverse proxy and forwards PHP requests to the Laravel application runtime.

## 3. Application Layer
The Laravel application contains:
- authentication
- dashboards
- appointment logic
- doctor availability logic
- specialty management
- reporting and admin features

## 4. Database Layer
MySQL stores operational data:
- users
- doctor profiles
- specialties
- availabilities
- appointments
- reports
- related business entities

## 5. CI/CD Layer
Two CI/CD systems are prepared:
- GitHub Actions
- GitLab CI/CD

These pipelines validate code, build assets, run tests, and package delivery artifacts.

## 6. Deployment Layer
### Local deployment
Docker Compose orchestrates:
- app
- nginx
- mysql

### Future deployment
Kubernetes manifests prepare deployment for:
- app
- mysql
- nginx
- namespace
- services
- configmap
- secret
- persistent volume claim

## 7. Operations Layer
Operational support includes:
- backup script
- monitoring plan
- Terraform IaC starter files

## Text Diagram

User Browser
    |
    v
Nginx Reverse Proxy
    |
    v
Laravel Application (PHP-FPM)
    |
    v
MySQL Database

GitHub / GitLab
    |
    v
CI/CD Pipelines
    |
    v
Validation / Build / Test / Package / Deployment Preparation

Operations Support
- Backup
- Monitoring
- Terraform

## Deployment Notes
The current project delivers a strong academic DevOps implementation with local execution, automated validation, deployment preparation, and operational documentation.
The next professional step would be enabling production registry publishing and real cluster deployment.
