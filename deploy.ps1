# deploy.ps1
# Script to automate deployment of local changes to the VPS via GitHub and secure webhook.

# 1. Commit and push local changes to GitHub
Write-Host "Staging and committing local changes..." -ForegroundColor Cyan
git add .
git commit -m "Auto-deploy update from Antigravity"
Write-Host "Pushing changes to GitHub..." -ForegroundColor Cyan
git push

# 2. Trigger the VPS deployment webhook
$token = "pakdoelnet_deploy_secret_2026_xyz"
$url = "http://103.58.100.239/deploy-webhook"
Write-Host "Triggering VPS deployment webhook..." -ForegroundColor Cyan
try {
    $headers = @{
        "X-Deploy-Token" = $token
    }
    $response = Invoke-RestMethod -Uri $url -Method Post -Headers $headers -ContentType "application/json"
    if ($response.success) {
        Write-Host "Deployment successful! Output from VPS:" -ForegroundColor Green
        $response.output | Out-String
    } else {
        Write-Host "Deployment failed!" -ForegroundColor Red
        $response | ConvertTo-Json
    }
} catch {
    Write-Host "Error triggering webhook: $_" -ForegroundColor Red
}
