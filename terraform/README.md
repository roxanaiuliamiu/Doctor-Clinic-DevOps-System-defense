# Terraform IaC

This folder contains a simple Infrastructure as Code starter setup for the Doctor Clinic DevOps System.

## Purpose
The goal of this Terraform configuration is to demonstrate:
- infrastructure modularity
- variables and outputs
- repeatable environment description
- IaC project structure

## Files
- provider.tf
- variables.tf
- main.tf
- outputs.tf
- terraform.tfvars.example

## Usage
1. Install Terraform
2. Open this folder
3. Run:
   terraform init
   terraform plan
   terraform apply

## Note
This is a starter IaC layer for academic/project validation purposes.
It can later be extended for Proxmox, cloud VMs, networking, storage, or Kubernetes infrastructure.
