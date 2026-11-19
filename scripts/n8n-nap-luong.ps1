# Nap credential + luong SocialSync vao n8n, roi publish.
# Chay tu thu muc du an:  powershell -ExecutionPolicy Bypass -File scripts\n8n-nap-luong.ps1
# Chay lai bao nhieu lan cung duoc: credential va luong co ID co dinh nen se bi ghi de.
#
# Ma bi mat lay tu file .env (SOCIALSYNC_SECRET). Script khong in ma bi mat ra man hinh.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

function Write-Step($msg) { Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss.fff'), $msg) }

function Read-EnvValue($name, [switch]$Optional) {
  $line = Select-String -Path (Join-Path $root '.env') -Pattern ("^{0}=(.+)$" -f $name) | Select-Object -First 1
  if (-not $line) {
    if ($Optional) { return $null }
    throw "Khong thay $name trong file .env"
  }
  return $line.Matches[0].Groups[1].Value.Trim().Trim('"')
}

# 1. Doc ma bi mat va token tu .env (chi in do dai)
$secret = Read-EnvValue 'SOCIALSYNC_SECRET'
Write-Step ("SOCIALSYNC_SECRET: {0} ky tu" -f $secret.Length)
$fbToken = Read-EnvValue 'FB_PAGE_TOKEN' -Optional
if ($fbToken) { Write-Step ("FB_PAGE_TOKEN: {0} ky tu" -f $fbToken.Length) } else { Write-Step 'FB_PAGE_TOKEN: chua co, bo qua credential Facebook' }
$claudeKey = Read-EnvValue 'ANTHROPIC_API_KEY' -Optional
if ($claudeKey) { Write-Step ("ANTHROPIC_API_KEY: {0} ky tu, bat dau bang {1}" -f $claudeKey.Length, $claudeKey.Substring(0, [Math]::Min(7, $claudeKey.Length))) } else { Write-Step 'ANTHROPIC_API_KEY: chua co. Buoc AI se bao loi va luong tu dung noi dung mau.' }

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
if ($fbToken) {
  $cred += @{
    id   = 'SocSyncFbPage001'
    name = 'Facebook - token Trang Mint Shop'
    type = 'httpHeaderAuth'
    data = @{ name = 'Authorization'; value = "Bearer $fbToken" }
  }
}
# Luon tao credential Claude: n8n 2.x tu choi publish luong neu mot khoi tro toi credential khong ton tai.
# Chua co key thi dung gia tri giu cho: Claude tra 401, khoi "AI: kiem tra va ap noi dung" dung noi dung mau va ghi ly do.
$cred += @{
  id   = 'SocSyncClaude001'
  name = 'Claude - API key'
  type = 'httpHeaderAuth'
  data = @{ name = 'x-api-key'; value = $(if ($claudeKey) { $claudeKey } else { 'chua-cau-hinh-ANTHROPIC_API_KEY' }) }
}
try {
  $json = ConvertTo-Json -InputObject $cred -Depth 5
  [System.IO.File]::WriteAllText($tmp, $json, (New-Object System.Text.UTF8Encoding($false)))
  Write-Step ("Nap {0} credential vao n8n" -f $cred.Count)
  docker exec shop-n8n-1 n8n import:credentials --input=/files/.tmp-credential.json
  if ($LASTEXITCODE -ne 0) { throw "import:credentials loi, exit $LASTEXITCODE" }
}
finally {
  if (Test-Path $tmp) { Remove-Item $tmp -Force; Write-Step 'Da xoa file credential tam' }
}

# 3a. Nap luong bao loi (luong chinh tro toi no qua settings.errorWorkflow = SocialSyncError01)
Write-Step 'Nap luong bao loi n8n/workflows/socialsync-error.json'
docker exec shop-n8n-1 n8n import:workflow --input=/files/workflows/socialsync-error.json
if ($LASTEXITCODE -ne 0) { throw "import:workflow (bao loi) loi, exit $LASTEXITCODE" }

# 3a2. Nap luong bao cao tuan (lich thu Hai 08:00 + webhook socialsync/report)
Write-Step 'Nap luong bao cao n8n/workflows/socialsync-report.json'
docker exec shop-n8n-1 n8n import:workflow --input=/files/workflows/socialsync-report.json
if ($LASTEXITCODE -ne 0) { throw "import:workflow (bao cao) loi, exit $LASTEXITCODE" }

# 3b. Nap luong chinh. Dia chi webhook Make (MAKE_PINTEREST_WEBHOOK) khong nam trong repo: chen vao ban sao tam
#    cua file luong thay cho chuoi giu cho __MAKE_PINTEREST_WEBHOOK__, nap ban sao, roi xoa.
$makeHook = Read-EnvValue 'MAKE_PINTEREST_WEBHOOK' -Optional
$wfSrc = Join-Path $root 'n8n\workflows\socialsync-main.json'
$wfTmp = Join-Path $root 'n8n\.tmp-workflow.json'
$wfText = [System.IO.File]::ReadAllText($wfSrc, (New-Object System.Text.UTF8Encoding($false)))
$placeholder = '__MAKE_PINTEREST_WEBHOOK__'
$hits = ([regex]::Matches($wfText, [regex]::Escape($placeholder))).Count
Write-Step ("Chuoi giu cho webhook Make trong file luong: {0} cho" -f $hits)
if ($hits -ne 1) { throw "Chuoi giu cho phai xuat hien dung 1 lan (URL cua khoi 'Pinterest: gui sang Make'), dang co $hits" }
if ($makeHook) {
  if ($makeHook -notmatch '^https://[^/\s"]+/[^\s"]+$') { throw 'MAKE_PINTEREST_WEBHOOK trong .env khong phai dia chi https hop le' }
  $hookHost = ([uri]$makeHook).Host
  Write-Step ("MAKE_PINTEREST_WEBHOOK: host {0}, {1} ky tu (khong in duong dan)" -f $hookHost, $makeHook.Length)
  $wfText = $wfText.Replace($placeholder, $makeHook)
} else {
  Write-Step 'CANH BAO: chua co MAKE_PINTEREST_WEBHOOK trong .env. Luot Pinterest se bao "failed" kem huong dan.'
}
try {
  [System.IO.File]::WriteAllText($wfTmp, $wfText, (New-Object System.Text.UTF8Encoding($false)))
  Write-Step 'Nap luong (ban sao tam cua n8n/workflows/socialsync-main.json)'
  docker exec shop-n8n-1 n8n import:workflow --input=/files/.tmp-workflow.json
  if ($LASTEXITCODE -ne 0) { throw "import:workflow loi, exit $LASTEXITCODE" }
}
finally {
  if (Test-Path $wfTmp) { Remove-Item $wfTmp -Force; Write-Step 'Da xoa file luong tam' }
}

# 4. Publish. Luong bao loi chi co Error Trigger: n8n co the khong can (hoac khong cho) publish, nen loi o day khong dung script.
Write-Step 'Publish luong SocialSyncError01 (khong bat buoc)'
docker exec shop-n8n-1 n8n publish:workflow --id=SocialSyncError01
if ($LASTEXITCODE -ne 0) { Write-Step "Ghi chu: publish luong bao loi tra exit $LASTEXITCODE (Error Trigger thuong khong can publish)" }
Write-Step 'Publish luong SocialSyncReport01'
docker exec shop-n8n-1 n8n publish:workflow --id=SocialSyncReport01
if ($LASTEXITCODE -ne 0) { throw "publish:workflow (bao cao) loi, exit $LASTEXITCODE" }
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
