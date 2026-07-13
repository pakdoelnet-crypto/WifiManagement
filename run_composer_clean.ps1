$env:PATH = "D:\php;" + $env:PATH
Write-Output "Running composer install..."
composer install --no-interaction
Write-Output "composer install finished!"
