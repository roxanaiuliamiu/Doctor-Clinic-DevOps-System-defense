$timestamp = Get-Date -Format "yyyy-MM-dd-HH-mm-ss"
$backupDir = "backups\$timestamp"

New-Item -ItemType Directory -Force -Path $backupDir | Out-Null

docker compose exec -T mysql mysqldump -uroot -proot doktors > "$backupDir\db.sql"

Compress-Archive -Path "storage\app\public\*" -DestinationPath "$backupDir\storage.zip" -Force

Write-Host "Backup completed in $backupDir"
