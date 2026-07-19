# Import and deploy script for Windows XAMPP
# Usage: Run PowerShell as Administrator and execute:
#   powershell -ExecutionPolicy Bypass -File .\scripts\import_and_deploy.ps1

$ErrorActionPreference = 'Stop'

$source = "C:\Users\User\OneDrive\Desktop\muslim_healthcare_centre design"
$dest = "C:\xampp\htdocs\muslim_healthcare_centre"
$sqlFile = Join-Path $source 'database.sql'

Write-Host "Source: $source"
Write-Host "Destination: $dest"
Write-Host "SQL file: $sqlFile"

# Find mysql executable
$possible = @(
    'C:\xampp\mysql\bin\mysql.exe',
    'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe',
    'C:\Program Files (x86)\MySQL\MySQL Server 5.7\bin\mysql.exe'
)
$mysqlExe = $possible | Where-Object { Test-Path $_ } | Select-Object -First 1
if (-not $mysqlExe) {
    Write-Host "mysql.exe not found in common XAMPP/MySQL locations. Attempting to use 'mysql' from PATH..."
    $mysqlExe = 'mysql'
}
Write-Host "Using MySQL executable: $mysqlExe"

# Prompt for MySQL user and password
$dbUser = Read-Host "MySQL username (default: root)"; if ([string]::IsNullOrWhiteSpace($dbUser)) { $dbUser = 'root' }
$dbPass = Read-Host "MySQL password (press Enter for empty)" -AsSecureString
$dbPassPlain = [Runtime.InteropServices.Marshal]::PtrToStringAuto([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPass))

# Copy files to htdocs
Write-Host "Copying files to $dest..."
if (-Not (Test-Path $dest)) { New-Item -ItemType Directory -Path $dest -Force | Out-Null }
robocopy $source $dest /MIR /XD scripts node_modules .git
if ($LastExitCode -gt 8) { Write-Host "robocopy reported an error code: $LastExitCode"; throw "Copy failed" }
Write-Host "Files copied."

# Create database and import
if (-Not (Test-Path $sqlFile)) { throw "SQL file not found: $sqlFile" }

Write-Host "Creating database 'healthcare_centre' if not exists..."
$createCmd = "CREATE DATABASE IF NOT EXISTS healthcare_centre CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if ($dbPassPlain -eq '') {
    & $mysqlExe -u $dbUser -e $createCmd
} else {
    & $mysqlExe -u $dbUser -p$dbPassPlain -e $createCmd
}
Write-Host "Database created/exists. Importing SQL (this may take a while)..."

# Import using piping to handle < redirection
$sqlContent = Get-Content -Raw -Path $sqlFile
if ($dbPassPlain -eq '') {
    $proc = Start-Process -FilePath $mysqlExe -ArgumentList "-u", "$dbUser", "healthcare_centre" -NoNewWindow -RedirectStandardInput "temp" -PassThru
    $proc.StandardInput.WriteLine($sqlContent)
    $proc.StandardInput.Close()
    $proc.WaitForExit()
    if ($proc.ExitCode -ne 0) { throw "mysql import exited with code $($proc.ExitCode)" }
} else {
    $proc = Start-Process -FilePath $mysqlExe -ArgumentList "-u", "$dbUser", "-p$dbPassPlain", "healthcare_centre" -NoNewWindow -RedirectStandardInput "temp" -PassThru
    $proc.StandardInput.WriteLine($sqlContent)
    $proc.StandardInput.Close()
    $proc.WaitForExit()
    if ($proc.ExitCode -ne 0) { throw "mysql import exited with code $($proc.ExitCode)" }
}

Write-Host "Import complete."
Write-Host "Done. Visit http://localhost/muslim_healthcare_centre/ to test the site."

# Cleanup secure string
[Runtime.InteropServices.Marshal]::ZeroFreeBSTR([Runtime.InteropServices.Marshal]::SecureStringToBSTR($dbPass))
