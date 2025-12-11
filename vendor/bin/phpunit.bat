@echo off
REM PHPUnit wrapper - uses %PHP_PATH% if set, otherwise defaults to XAMPP PHP
IF "%PHP_PATH%"=="" SET "PHP_PATH=C:\xampp\php\php.exe"
"%PHP_PATH%" "%~dp0phpunit.phar" %*
