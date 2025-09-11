# PowerShell script to package WordPress plugin
# Usage: Run this script from the root of your project

# Plugin packaging with exclude list
$PluginName = "musahimoun"
$ZipFile = "$PluginName.zip"
$TempDir = "_plugin_pack_temp"

# Clean up any previous temp directory
if (Test-Path $TempDir) {
    Remove-Item -Recurse -Force $TempDir
}

# Create temp directory
New-Item -ItemType Directory -Path $TempDir | Out-Null

# Exclude list: files and folders NOT to include in the package (regex patterns)
$ExcludeList = @(
    [regex]::Escape($TempDir),
    [regex]::Escape($ZipFile),
    "^\.git($|\\)",
    "^\.gitignore($|\\)",
    "^\.prettierrc.json($|\\)",
    "node_modules($|\\)",
    "front\\node_modules($|\\)",
    "tests($|\\)",
    "build($|\\)",
    "\.vscode($|\\)",
    "Dockerfile",
    "docker-compose\.yml",
    ".*\.ps1$",
    "README\.md$",
    "README\.ar\.md$",
    "languages($|\\)",
    "assets($|\\)",
    "vendor($|\\)"
)

# Recursively copy only files/folders not matching exclude patterns
Get-ChildItem -Path . -Recurse -Force | ForEach-Object {
    $relativePath = $_.FullName.Substring((Get-Location).Path.Length + 1)
    $relativePath = $relativePath -replace '/', '\' # Normalize to Windows path
    $exclude = $false
    foreach ($pattern in $ExcludeList) {
        if ($relativePath -match $pattern) {
            $exclude = $true
            break
        }
    }
    if (-not $exclude) {
        $dest = Join-Path $TempDir $relativePath
        if ($_.PSIsContainer) {
            if (-not (Test-Path $dest)) {
                New-Item -ItemType Directory -Path $dest | Out-Null
            }
        } else {
            Copy-Item $_.FullName -Destination $dest -Force
        }
    }
}

# ...existing code...

# Create ZIP archive
if (Test-Path $ZipFile) {
    Remove-Item $ZipFile
}
Compress-Archive -Path "$TempDir\*" -DestinationPath $ZipFile

# Clean up temp directory
Remove-Item -Recurse -Force $TempDir

Write-Host "Plugin packaged as $ZipFile"
