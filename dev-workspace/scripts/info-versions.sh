#!/usr/bin/env bash

echo "PHP Version: " $(php -v)
echo "Composer Version: " $(composer --version)
echo "Node Version: " $(node -v)
echo "NPM Version: " $(npm -v)
echo "Yarn Version: " $(yarn -v)
echo "WP-CLI Version: " $(wp --version --allow-root)
echo "Codeception Version: " $(vendor/bin/codecept --version)
echo "PHP CodeSniffer Version: " $(phpcs --version)
echo "PHP Mess Detector Version: " $(phpmd --version)
echo "PHP Copy/Paste Detector Version: " $(phpcpd --version)
echo "PHP Lint Version: " $(phplint --version)
echo "PHPStan Version: " $(phpstan --version)
echo "Builder Version:" "$(pbuild version | grep -o 'PUBLISHPRESS PLUGIN BUILDER - v[0-9]\+\.[0-9]\+\.[0-9]\+')"
