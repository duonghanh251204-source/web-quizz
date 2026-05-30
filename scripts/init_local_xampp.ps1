$php = 'E:\xampp\php\php.exe'
if (!(Test-Path $php)) {
    Write-Error "XAMPP PHP not found at $php"
    exit 1
}

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

if (!(Test-Path '.env')) {
    Copy-Item -LiteralPath '.env.example' -Destination '.env' -Force
    Write-Output '.env created from .env.example'
} else {
    Write-Output '.env already exists'
}

& $php scripts\init_db.php
