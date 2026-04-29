# Convert TTF to WOFF2 for faster font delivery
# Usage: from repository root run `pwsh .\tools\convert-fonts.ps1`
# Requires Node.js + ttf2woff2 or the woff2_compress binary available in PATH.

$fontsDir = "assets/lib/fonts"
Write-Host "Converting TTF -> WOFF2 in $fontsDir"

# Ensure node tool exists
function Convert-With-Npx {
  param($ttf)
  $out = [System.IO.Path]::ChangeExtension($ttf, '.woff2')
  Write-Host "Using npx ttf2woff2 for $ttf -> $out"
  & npx ttf2woff2 $ttf > $out
}

function Convert-With-Woff2Compress {
  param($ttf)
  $out = [System.IO.Path]::ChangeExtension($ttf, '.woff2')
  Write-Host "Using woff2_compress for $ttf -> $out"
  & woff2_compress $ttf
}

Get-ChildItem -Path $fontsDir -Filter *.ttf -File | ForEach-Object {
  $ttfPath = $_.FullName
  $woff2Path = [System.IO.Path]::ChangeExtension($ttfPath, '.woff2')
  if (Test-Path $woff2Path) {
    Write-Host "Skipping existing: $woff2Path"
    return
  }

  try {
    if (Get-Command npx -ErrorAction SilentlyContinue) {
      Convert-With-Npx $ttfPath
    } elseif (Get-Command woff2_compress -ErrorAction SilentlyContinue) {
      Convert-With-Woff2Compress $ttfPath
    } else {
      Write-Error "Neither 'npx' nor 'woff2_compress' found. Install Node.js and run 'npm i -g ttf2woff2' or install woff2 tools."
    }
  } catch {
    Write-Error "Failed to convert $ttfPath : $_"
  }
}

Write-Host "Done. Verify .woff2 files in $fontsDir and commit them to repo."