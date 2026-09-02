[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [ValidatePattern('^\d+\.\d+\.\d+$')] [string] $Version,
    [Parameter(Mandatory = $true)] [string] $OutputDirectory
)

$ErrorActionPreference = 'Stop'
$repository = Split-Path -Parent $PSScriptRoot
$header = Get-Content -Raw (Join-Path $repository 'view-privacy.php')
if ($header -notmatch "(?m)^ \* Version:\s+$([regex]::Escape($Version))\r?$") {
    throw "Plugin header version does not match requested package version $Version."
}

$output = [System.IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Force -Path $output | Out-Null
$staging = Join-Path ([System.IO.Path]::GetTempPath()) ("view-privacy-package-" + [guid]::NewGuid())
New-Item -ItemType Directory -Path (Join-Path $staging 'view-privacy') | Out-Null
try {
    Get-ChildItem -Force $repository | Where-Object { $_.Name -notin @('.git', '.github', 'dist', 'tests', 'scripts') } | Copy-Item -Destination (Join-Path $staging 'view-privacy') -Recurse -Force
    $zip = Join-Path $output "view-privacy-$Version.zip"
    Compress-Archive -Path (Join-Path $staging 'view-privacy') -DestinationPath $zip -Force
    & (Join-Path $PSScriptRoot 'validate-release-package.ps1') -PackagePath $zip -Version $Version
    Write-Output $zip
} finally {
    Remove-Item -LiteralPath $staging -Recurse -Force
}
