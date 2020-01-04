GeneratorBundle
=====================

A Symfony bundle for quickly generating/prototyping a CRUD application. Compatible with
Symfony 5 and php 7.4+

This bundle is similar to Symfony's [MakerBundle](https://github.com/symfony/maker-bundle),
but with a different approach to be more extensible.


## Getting started

* `todo: composer install`
* `php bin/console assets:install`
* Add the following the `config/routes.yaml`   
    generator:
        resource: '@GeneratorBundle/resources/config/routes.yaml'
