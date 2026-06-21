param(
    [switch]$Install,
    [switch]$Reset,
    [switch]$NoBuild
)

$argsList = @()
if ($Install) { $argsList += "--install" }
if ($Reset) { $argsList += "--reset" }
if ($NoBuild) { $argsList += "--no-build" }

python scripts/bootstrap_environment.py @argsList
