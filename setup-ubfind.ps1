if (-not (Get-Command php -ErrorAction SilentlyContinue)) {
    Write-Error "PHP executable not found in PATH. Install PHP or add it to PATH and rerun."
    exit 1
}

# Optional DB setup (assume XAMPP mysql)
if (Get-Command mysql -ErrorAction SilentlyContinue) {
    Write-Host "Setting up DB ub_find..."
    Invoke-Expression "mysql -u root -e `"CREATE DATABASE IF NOT EXISTS ub_find;`""
    Write-Host "DB ub_find ready. Run 'mysql ub_find < db/schema.sql' if needed."
}

$siteUrl = "http://localhost:8000/"
$docRoot = (Get-Location).Path
$serverCommand = "php -S localhost:8000 -t ."
Write-Host "Starting PHP built-in server at $siteUrl"
Start-Process powershell -ArgumentList @("-NoExit", "-Command", $serverCommand) -WorkingDirectory $docRoot
Start-Sleep -Seconds 1
Write-Host "Opening browser to $siteUrl"
Start-Process $siteUrl
