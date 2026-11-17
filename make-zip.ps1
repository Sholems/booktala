$src = "C:\laragon\www\shortlet_apartment"
$dest = "C:\laragon\www\shortlet_apartment\deployment.zip"

if (Test-Path $dest) { Remove-Item $dest }

$items = @(
    "app",
    "bootstrap",
    "config",
    "database",
    "public",
    "resources",
    "routes",
    "storage",
    "artisan",
    "composer.json",
    "vendor"
)

Write-Host "Creating deployment ZIP..."
Write-Host "Source: $src"
Write-Host "Destination: $dest"

Compress-Archive -Path "$src\*" -DestinationPath $dest -Force

if (Test-Path $dest) {
    $size = (Get-Item $dest).Length / 1MB
    Write-Host "Success! Created deployment.zip ($([math]::Round($size, 2)) MB)"
} else {
    Write-Host "Error: Failed to create ZIP"
}