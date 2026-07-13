$env:PATH = "D:\php;" + $env:PATH
Write-Output "Running composer require for socialite..."
composer require laravel/socialite --no-interaction
Write-Output "composer require finished!"
