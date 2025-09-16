# PowerShell script to package the Musahimoun plugin for release

param(
    [string[]]$Include = @(),
    [string[]]$Exclude = @()
)

$ErrorActionPreference = 'Stop'

# -------------------------------
# Helper functions
# -------------------------------

function Get-StableTag {
    $readme = Get-Content -Raw -Path './README.txt'
    if ($readme -match 'Stable tag:\s*([\w\.-]+)') {
        return $matches[1]
    } else {
        throw 'Stable tag not found in README.txt'
    }
}

function Get-GitIgnorePatterns {
    $patterns = @()
    if (Test-Path .gitignore) {
        $patterns = Get-Content .gitignore | Where-Object { $_ -and -not $_.StartsWith('#') }
    }
    return $patterns
}

function Copy-Project {
    param(
        [string]$Dest,
        [string[]]$ExtraExclude = @(),
        [switch]$IsTrunk
    )

     # Only trunk respects .gitignore exclusions
    $gitignore = if ($IsTrunk) { Get-GitIgnorePatterns } else { @() }
    $allExclude = $gitignore + $ExtraExclude + $Exclude
    $allInclude = $Include
    $srcRoot = (Get-Location).Path

    # Absolute path for destination
    if ([System.IO.Path]::IsPathRooted($Dest)) {
        $destRoot = $Dest
    } else {
        $destRoot = Join-Path $srcRoot $Dest
    }

    # Clean destination folder
    if (Test-Path $destRoot) { Remove-Item $destRoot -Recurse -Force }
    New-Item -ItemType Directory -Path $destRoot -Force | Out-Null

    # Convert glob patterns to regex
    $excludeRegex = $allExclude | ForEach-Object {
        $pattern = $_ -replace '\\','/'          # normalize slashes
        $pattern = [Regex]::Escape($pattern)     # escape special chars
        $pattern = $pattern -replace '\\\*', '.*' -replace '\\\?', '.'
        $pattern
    }

    # Immediate folders to skip entirely
    $skipFolders = @('node_modules','vendor', 'languages')

    # Get all files recursively from source folder
    $allFiles = Get-ChildItem -Recurse -File -Force -ErrorAction SilentlyContinue
    $filteredFiles = @()
    $total = $allFiles.Count
    $i = 0

    foreach ($file in $allFiles) {
        $i++
        $rel = $file.FullName.Substring($srcRoot.Length+1) -replace '\\','/'

        # Skip big folders immediately
        $skip = $false
        foreach ($folder in $skipFolders) {
            if ($rel -match "(^|/)$folder(/|$)") { $skip = $true; break }
        }
        if ($skip) { continue }

        # Trunk exclusion
        if ($IsTrunk -and $rel -match '^front/dist') { continue }

        # Include filter
        if ($allInclude.Count -gt 0 -and ($allInclude | Where-Object { $rel -like $_ }) -eq $null) { continue }

        # Exclude filter
        $exclude = $false
        foreach ($rx in $excludeRegex) {
            if ($rel -match $rx) { $exclude = $true; break }
        }
        if ($exclude) { continue }

        $filteredFiles += $file
        Write-Progress -Activity "Copying project files" -Status "$i of $total" -PercentComplete (($i / $total) * 100)
    }

    # Copy files
    foreach ($file in $filteredFiles) {
        $rel = $file.FullName.Substring($srcRoot.Length+1) -replace '\\','/'
        $target = Join-Path $destRoot $rel
        $targetDir = Split-Path $target -Parent
        if (!(Test-Path $targetDir)) { New-Item -ItemType Directory -Path $targetDir -Force | Out-Null }
        Copy-Item $file.FullName $target -Force
    }

    Write-Progress -Activity "Copying project files" -Completed
}

function Copy-Assets {
    param([string]$Dest)
    if (Test-Path 'assets') {
        $target = Join-Path $Dest 'assets'
        if (Test-Path $target) { Remove-Item $target -Recurse -Force }
        Copy-Item 'assets' $target -Recurse -Force
    }
}

# -------------------------------
# Main
# -------------------------------

$version = Get-StableTag
$mainFolder = "musahimoun-release-$version"
$mainPath = Join-Path $PWD $mainFolder

if (Test-Path $mainPath) { Remove-Item $mainPath -Recurse -Force }
New-Item -ItemType Directory -Path $mainPath -Force | Out-Null

# 1. Build trunk
$trunkExclude = @('.gitignore*', '.git*', 'assets', '.vscode')
Copy-Project -Dest (Join-Path $mainPath 'trunk') -ExtraExclude $trunkExclude -IsTrunk

# 2. Build version folder
$tagExclude = @(
    'package.json*',
    'package-lock.json*',
    'composer.json*',
    'composer.lock*',
    'package-plugin.ps1*',
    'package-release.ps1*',
    'yarn.lock',
    'pnpm-lock.yaml',
    'Dockerfile*',
    'docker-compose*',
    '.env*',
    '.git*',
    '*.md',
    '*.MD',
    'node_modules*',
    'tests*',
    'test*',
    '^\..+',          # hidden files
    'docs*',
    'front/src',
    'tsconfig.json*',
    'webpack.config*',
    '.vscode*',
    'languages',
    'musahimoun-release-*',  # avoid nesting releases
    'assets',
    '*.zip'  # avoid including zip files
)

$versionDest = Join-Path (Join-Path $mainPath 'tags') $version
Copy-Project -Dest $versionDest -ExtraExclude $tagExclude

# 3. Copy assets
Copy-Assets -Dest $mainPath

Write-Host "Packaging complete. Output:"
Write-Host "  $mainFolder/trunk/"
Write-Host "  $mainFolder/tags/$version/"
Write-Host "  $mainFolder/assets/"
