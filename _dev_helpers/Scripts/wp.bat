

SET mypath=%~dp0
php %mypath:~0,-1%\wp-cli.phar %*
