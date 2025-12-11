$php = $env:PHP_PATH
if (-not $php) { $php = 'C:\xampp\php\php.exe' }
& $php "$PSScriptRoot\phpunit.phar" @args
