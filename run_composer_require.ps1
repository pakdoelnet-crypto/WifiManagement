$env:PATH = "D:\php;" + $env:PATH
Write-Output "Running composer require..."
composer require evilfreelancer/routeros-api-php --no-interaction
Write-Output "composer require finished!"
