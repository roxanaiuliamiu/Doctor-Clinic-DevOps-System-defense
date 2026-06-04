# Doctor Clinic DevOps System

A Laravel-based clinic management and appointment booking platform with a complete DevOps delivery layer — containerized, automatically tested, deployed to a live server, and monitored in real time.

**Live Application:** http://51.158.200.127:8080  
**Grafana Monitoring:** http://51.158.200.127:3000  
**CI/CD:** GitLab Pipelines — auto-deploy on every push to `main`

---

## Project Overview

The Doctor Clinic system supports three user roles:

| Role | Capabilities |
|------|-------------|
| Patient | Register, browse doctors and specialties, book appointments, patient dashboard |
| Doctor | Manage profile, availability, and appointments |
| Admin | Manage users, specialties, reports, settings, approvals |

---

## Technology Stack

| Layer | Technologies |
|-------|-------------|
| Application | Laravel 12, PHP 8.2, Blade, Tailwind CSS, Alpine.js, Vite |
| Database | MySQL 8.0 |
| Containerization | Docker CE, Docker Compose v2, Nginx Alpine |
| CI/CD | GitLab CI/CD (5 stages: validate → build → test → package → deploy) |
| Live Deployment | Proxmox VE 8.4, Ubuntu 22.04 VM, iptables NAT |
| Monitoring | Prometheus + Node Exporter + Grafana (persistent volumes) |
| Deployment Prep | Kubernetes YAML manifests (10 files) |
| IaC | Terraform (5 files) |
| Runner | GitLab Runner 19.0.1 on Proxmox VM (tag: proxmox) |

---

## Quick Start (Local)

### Prerequisites
- Docker and Docker Compose installed
- Git

### Steps

```bash
# 1. Clone the repository
git clone https://gitlab.com/MunirAltawil/Doctor-Clinic-DevOps-System.git
cd Doctor-Clinic-DevOps-System

# 2. Copy environment file
cp .env.example .env

# 3. Configure database in .env
DB_HOST=mysql
DB_DATABASE=doktors
DB_USERNAME=doktors_user
DB_PASSWORD=doktors_pass

# 4. Build and start containers
docker compose up -d --build

# 5. Install PHP dependencies
docker compose exec app composer install

# 6. Generate application key
docker compose exec app php artisan key:generate

# 7. Run migrations and seeders
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force

# 8. Build frontend assets
docker compose exec app npm install
docker compose exec app npm run build

# 9. Fix storage permissions
docker compose exec app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
```

Application available at: **http://localhost:8080**

---

## Repository Structure

```
app/                    Laravel application code
bootstrap/              Laravel bootstrap files
config/                 Application configuration
database/
  migrations/           16 database migrations
  seeders/              9 database seeders
docker/
  nginx/                Nginx configuration
k8s/                    10 Kubernetes YAML manifests
monitoring/
  prometheus.yml        Prometheus scrape configuration
public/                 Public web root
resources/              Views, CSS, JS sources
routes/                 Laravel route definitions
scripts/                Backup automation scripts
terraform/              IaC files (5 files)
tests/                  PHPUnit test suite
.gitlab-ci.yml          GitLab CI/CD pipeline (5 stages)
docker-compose.yml      Full stack: app + monitoring + persistent volumes
Dockerfile              PHP 8.2-FPM application image
```

---

## CI/CD Pipeline (GitLab)

The pipeline runs **automatically on every push to `main`**:

| Stage | Job | Description |
|-------|-----|-------------|
| validate | php_syntax | PHP lint on all source files |
| build | build_frontend | npm ci + npm run build (Vite) |
| test | test_application | SQLite + migrations + PHPUnit |
| package | package_project | tar.gz release archive |
| deploy | deploy_production | SSH auto-deploy to Proxmox VM |

### What deploy_production does automatically:
1. Copies `monitoring/prometheus.yml` to the server via SCP
2. Stops and removes old containers
3. Pulls latest images
4. Starts all containers via `docker compose up -d`
5. Starts Node Exporter for host metrics
6. Starts Prometheus with the correct scrape config
7. Verifies all containers are running

### GitLab Runner
- **Runner:** Doctors-Clinic-VM (#53489811)
- **Host:** 51.158.200.127
- **Tag:** proxmox
- **Version:** GitLab Runner 19.0.1

---

## Live Deployment (Proxmox)

| Property | Value |
|----------|-------|
| Public URL | http://51.158.200.127:8080 |
| Proxmox Host | april26-bootcamp-dataops |
| VM | 100 / Doctors-Clinic-DevOps-System |
| OS | Ubuntu 22.04.5 LTS |
| VM Resources | 2 vCPU / 4 GB RAM / 22 GB Disk |
| Internal IP | 10.10.10.100 |
| Network | vmbr1 + iptables NAT (ports: 8080, 3000, 9090) |

### Running Containers

```
NAMES                  STATUS        PORTS
doktors_nginx          Up            0.0.0.0:8080->80/tcp
doktors_app            Up            9000/tcp
doktors_mysql          Up            0.0.0.0:3307->3306/tcp
doktors_grafana        Up            0.0.0.0:3000->3000/tcp
doktors_prometheus     Up            9090/tcp
node_exporter          Up            9100/tcp
```

### Persistent Volumes

| Volume | Purpose |
|--------|---------|
| doktors_mysql_data | MySQL database persistence |
| prometheus_data | Prometheus metrics history (15 days retention) |
| grafana_data | Grafana dashboards and data sources |

---

## Monitoring Stack (Prometheus + Grafana)

### Architecture

```
[Node Exporter :9100] ──► [Prometheus :9090] ──► [Grafana :3000]
        │                        │                      │
  Host metrics              Stores metrics         Dashboards &
  CPU/RAM/Disk/Net          (15d retention)        Visualization
                                                   (persistent)
```

### Access

| Service | URL | Credentials |
|---------|-----|-------------|
| Grafana | http://51.158.200.127:3000 | admin / admin |
| Prometheus | http://51.158.200.127:9090 | — |
| Node Exporter | port 9100 (internal) | — |

### Metrics Collected

| Category | Metrics |
|----------|---------|
| CPU | Usage %, system load (1m/5m/15m) |
| Memory | RAM used, cached, free, swap |
| Disk | Filesystem usage per mount point |
| Network | Inbound/outbound traffic per interface |
| System | Uptime, processes |

### Grafana Dashboard

The Grafana dashboard and data source configuration are **automatically persisted** via Docker volumes. After initial setup, dashboards and settings survive container restarts and redeployments.

**First-time setup only:**
1. Open http://51.158.200.127:3000 → login: `admin` / `admin`
2. **Connections → Data sources → prometheus** → URL: `http://10.10.10.100:9090` → **Save & test**
3. **Dashboards → New → Import** → ID: `1860` → **Import**

### prometheus.yml

```yaml
global:
  scrape_interval: 15s
  evaluation_interval: 15s
  external_labels:
    monitor: 'doctor-clinic-monitor'

scrape_configs:
  - job_name: 'prometheus'
    static_configs:
      - targets: ['localhost:9090']

  - job_name: 'node'
    static_configs:
      - targets: ['localhost:9100']
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'Doctors-Clinic-DevOps-System'

  - job_name: 'docker'
    static_configs:
      - targets: ['localhost:9323']
```

---

## Kubernetes (Future Deployment)

Manifests in `k8s/`:
- `namespace.yaml`, `secrets.yaml`, `pvc.yaml`
- `mysql-deployment.yaml`, `mysql-service.yaml`
- `app-deployment.yaml`, `app-service.yaml`
- `nginx-configmap.yaml`, `nginx-deployment.yaml`, `nginx-service.yaml`

```bash
kubectl apply -f k8s/
```

---

## Infrastructure as Code (Terraform)

```bash
cp terraform/terraform.tfvars.example terraform/terraform.tfvars
terraform init
terraform plan
terraform apply
```

---

## Backup

```bash
./scripts/backup.ps1
# Creates: MySQL dump + public/storage archive
```

---

## Documentation

| Document | Description |
|----------|-------------|
| 01_Project_Specifications | Business need, objectives, technology stack |
| 02_Architecture_and_Diagrams | System architecture, network diagram, CI/CD flow |
| 03_Requirements_Mapping | Institute requirements coverage matrix |
| 04_Delivery_Checklist | Final submission evidence checklist |
| 05_Executive_Report | Full project report with challenges and results |
| 06_Proxmox_Deployment_Evidence | Live deployment proof with commands and outputs |

---

## Author

**Muhammed Munir Al Tawil**  
DevOps – Application Deployment and Lifecycle  
DataScientest – Sorbonne University Partnership – 2026  
Mentor: Durrell Gemuh