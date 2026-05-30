param(
    [int]$Port = 8080
)

$php = 'E:\xampp\php\php.exe'
if (!(Test-Path $php)) {
    Write-Error "XAMPP PHP not found at $php"
    exit 1
}

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

Write-Output "Starting PRX at http://127.0.0.1:$Port"
& $php -S ("127.0.0.1:{0}" -f $Port) -t public
