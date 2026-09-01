[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)] [string] $PackagePath,
    [Parameter(Mandatory = $true)] [ValidatePattern('^\d+\.\d+\.\d+$')] [string] $Version
)

$ErrorActionPreference = 'Stop'
$package = Get-Item -LiteralPath $PackagePath
if ($package.Name -cne "view-privacy-$Version.zip") { throw 'Release asset must use the exact name view-privacy-X.Y.Z.zip.' }
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($package.FullName)
try {
    $entries = @($archive.Entries | Where-Object { -not $_.FullName.EndsWith('/') })
    $roots = @($entries | ForEach-Object { $_.FullName.Split('/')[0] } | Select-Object -Unique)
    if ($roots.Count -ne 1 -or $roots[0] -cne 'view-privacy') { throw 'Package must contain exactly one root folder named view-privacy/.' }
    $plugin = $entries | Where-Object { $_.FullName -ceq 'view-privacy/view-privacy.php' }
    if (-not $plugin) { throw 'Package must contain view-privacy/view-privacy.php directly below its root.' }
    $content = [System.IO.StreamReader]::new($plugin.Open()).ReadToEnd()
    if ($content -notmatch "(?m)^ \* Version:\s+$([regex]::Escape($Version))\r?$") { throw 'Package plugin header version does not match asset version.' }
} finally {
    $archive.Dispose()
}
Write-Output 'Package validation passed.'
