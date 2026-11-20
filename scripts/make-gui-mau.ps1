# Gui 1 goi du lieu MAU sang webhook Make de Make "hoc" cau truc du lieu (luc dung scenario).
# Chay tu thu muc du an:  powershell -ExecutionPolicy Bypass -File scripts\make-gui-mau.ps1
#
# CANH BAO: neu scenario Make da co khoi "Create a Pin" va dang BAT, goi mau nay se tao 1 Pin that
# (anh demo SocialSync, tro ve trang chu website).
# Hoc lai cau truc (vi du sau khi them truong board): trong Make bam khoi Custom webhook -> "Redetermine data structure"
# (Make hien "Waiting for data"), roi moi chay script nay.
# Dia chi webhook lay tu MAKE_PINTEREST_WEBHOOK trong .env; script chi in host, khong in duong dan.

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root
function Write-Step($msg) { Write-Host ("[{0}] {1}" -f (Get-Date -Format 'HH:mm:ss.fff'), $msg) }

$line = Select-String -Path (Join-Path $root '.env') -Pattern '^MAKE_PINTEREST_WEBHOOK=(.+)$' | Select-Object -First 1
if (-not $line) { throw 'Chua co MAKE_PINTEREST_WEBHOOK trong .env' }
$hook = $line.Matches[0].Groups[1].Value.Trim().Trim('"')
if ($hook -notmatch '^https://[^/\s"]+/[^\s"]+$') { throw 'MAKE_PINTEREST_WEBHOOK khong phai dia chi https hop le' }
Write-Step ("Webhook Make: host {0}, {1} ky tu" -f ([uri]$hook).Host, $hook.Length)

$reqId = -join ((48..57) + (97..102) | Get-Random -Count 12 | ForEach-Object { [char]$_ })
# Cung cau truc voi goi ma khoi "Pinterest: gui sang Make" trong n8n gui di.
$sample = [ordered]@{
  request_id  = $reqId
  job_id      = 0
  post_id     = 0
  title       = 'Thử đồng bộ SocialSync (gói mẫu)'
  description = 'Gói dữ liệu mẫu để Make nhận diện cấu trúc. Giá: 350.000 ₫'
  link        = 'https://shop.adaptivealgo.uk/?utm_source=pinterest&utm_medium=social&utm_campaign=socialsync'
  image_url   = 'https://shop.adaptivealgo.uk/wp-content/uploads/2026/11/socialsync-demo.jpg'
  alt_text    = 'Ảnh demo SocialSync'
  # Ten board Pinterest theo bang ghep danh muc (US-009). Make can thay truong nay de router chon board.
  board       = 'Làm đẹp'
}
$json = $sample | ConvertTo-Json -Depth 3
$bytes = [System.Text.Encoding]::UTF8.GetBytes($json)
Write-Step ("Gui goi mau request_id={0}, {1} byte" -f $reqId, $bytes.Length)

$sw = [System.Diagnostics.Stopwatch]::StartNew()
try {
  $resp = Invoke-WebRequest -Uri $hook -Method Post -Body $bytes -ContentType 'application/json; charset=utf-8' -Headers @{ 'X-SocialSync-Request-Id' = $reqId } -UseBasicParsing -TimeoutSec 60
  Write-Step ("Make tra HTTP {0} sau {1} ms" -f $resp.StatusCode, $sw.ElapsedMilliseconds)
  Write-Step ("Noi dung: {0}" -f $resp.Content)
} catch {
  Write-Step ("LOI sau {0} ms: {1}" -f $sw.ElapsedMilliseconds, $_.Exception.Message)
  if ($_.Exception.Response) {
    $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
    Write-Step ("Noi dung loi: {0}" -f $reader.ReadToEnd())
  }
  exit 1
}
