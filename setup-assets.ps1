# ============================================================
# setup-assets.ps1 — Golden Stone Hotel Automation
# Fixes the assets folder and copies the hotel background image
# Run this script once before opening index.html
# ============================================================

$projectRoot = $PSScriptRoot
$assetsPath  = Join-Path $projectRoot "assets"
$brainDir    = "C:\Users\Sanket\.gemini\antigravity\brain\d8ba4432-e299-4571-b572-21d5de09f0c1"

Write-Host "Setting up assets folder for Golden Stone Hotel..." -ForegroundColor Cyan

# Step 1: Remove the 0-byte file called "assets" if it exists as a file
if (Test-Path $assetsPath -PathType Leaf) {
    Remove-Item $assetsPath -Force
    Write-Host "  Removed 0-byte 'assets' file." -ForegroundColor Yellow
}

# Step 2: Create the assets directory
if (-not (Test-Path $assetsPath -PathType Container)) {
    New-Item -ItemType Directory -Path $assetsPath | Out-Null
    Write-Host "  Created 'assets' folder." -ForegroundColor Green
}

# Step 3: Copy hotel background image
$srcBg = Join-Path $brainDir "hotel_bg_1779955668648.png"
$dstBg = Join-Path $assetsPath "hotel-bg.jpg"
if (Test-Path $srcBg) {
    Copy-Item -Path $srcBg -Destination $dstBg -Force
    Write-Host "  Copied hotel background image -> assets\hotel-bg.jpg" -ForegroundColor Green
} else {
    Write-Host "  WARNING: Source image not found at $srcBg" -ForegroundColor Red
    Write-Host "  Please manually copy your hotel image to assets\hotel-bg.jpg" -ForegroundColor Red
}

Write-Host ""
Write-Host "Setup complete! Open index.html in a browser." -ForegroundColor Cyan
