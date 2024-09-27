In project root folder, run:

php -dpcov.enabled=1 -dpcov.directory=src/weightless/ -dpcov.exclude="~vendor~" ./vendor/bin/phpunit tests/ --coverage-html tests/coverage/ --coverage-filter=src/weightless/
