# Nap credential + luong SocialSync vao n8n, roi publish.
# Chay tu thu muc du an:  powershell -ExecutionPolicy Bypass -File scripts\n8n-nap-luong.ps1
# Chay lai bao nhieu lan cung duoc: credential va luong co ID co dinh nen se bi ghi de.
#
# Ma bi mat lay tu file .env (SOCIALSYNC_SECRET). Script khong in ma bi mat ra man hinh.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Write-Step($msg) { Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss.fff'), $msg) }

# 1. Doc ma bi mat tu .env
$line = Select-String -Path (Join-Path $root '.env') -Pattern '^SOCIALSYNC_SECRET=(.+)$' | Select-Object -First 1
if (-not $line) { throw 'Khong thay SOCIALSYNC_SECRET trong file .env' }
$secret = $line.Matches[0].Groups[1].Value.Trim()
Write-Step ("Doc ma bi mat tu .env: {0} ky tu" -f $secret.Length)

# 2. Tao file credential tam trong n8n/ (container thay o /files), nap vao n8n, xoa file
$tmp = Join-Path $root 'n8n\.tmp-credential.json'
$cred = @(
  @{
    id   = 'SocSyncSecret001'
    name = 'SocialSync - mã bí mật từ WordPress'
    type = 'httpHeaderAuth'
    data = @{ name = 'X-SocialSync-Secret'; value = $secret }
  }
)
try {
  $json = ConvertTo-Json -InputObject $cred -Depth 5
  [System.IO.File]::WriteAllText($tmp, $json, (New-Object System.Text.UTF8Encoding($false)))
  Write-Step 'Nap credential "SocialSync - ma bi mat tu WordPress"'
  docker exec shop-n8n-1 n8n import:credentials --input=/files/.tmp-credential.json
  if ($LASTEXITCODE -ne 0) { throw "import:credentials loi, exit $LASTEXITCODE" }
}
finally {
  if (Test-Path $tmp) { Remove-Item $tmp -Force; Write-Step 'Da xoa file credential tam' }
}

# 3. Nap luong
Write-Step 'Nap luong n8n/workflows/socialsync-main.json'
docker exec shop-n8n-1 n8n import:workflow --input=/files/workflows/socialsync-main.json
if ($LASTEXITCODE -ne 0) { throw "import:workflow loi, exit $LASTEXITCODE" }

# 4. Publish
Write-Step 'Publish luong SocialSyncMain01'
docker exec shop-n8n-1 n8n publish:workflow --id=SocialSyncMain01
if ($LASTEXITCODE -ne 0) { throw "publish:workflow loi, exit $LASTEXITCODE" }

# 5. Khoi dong lai n8n de dang ky webhook, cho toi khi san sang
Write-Step 'Khoi dong lai shop-n8n-1'
docker restart shop-n8n-1 | Out-Null
$ok = $false
for ($i = 1; $i -le 60; $i++) {
  Start-Sleep -Seconds 1
  $code = docker exec shop-wordpress-1 curl -s -o /dev/null -w '%{http_code}' http://n8n:5678/healthz/readiness
  if ($code -eq '200') { $ok = $true; Write-Step "n8n san sang sau $i giay"; break }
}
if (-not $ok) { throw 'n8n chua san sang sau 60 giay. Xem: docker logs --tail 80 shop-n8n-1' }

Write-Step 'Xong. Kiem tra bang: docker compose run --rm wpcli wp socialsync test'
