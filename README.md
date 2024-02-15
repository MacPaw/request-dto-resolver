Symfony Request DTO Resolver bundle
===============================
Automatically parses Symfony HTTP request, validates parameters, hydrates DTO and passes it as an argument
to your controller.

Installation
------------

Open a command console, switch to your project directory and execute:

```console
composer require macpaw/request-dto-resolver
```
Your bundle now should be automatically added to the list of registered bundles.

```php
// config/bundles.php
<?php
return [
            RequestDtoResolver\RequestDtoResolverBundle::class => ['all' => true],

        // ...
    ];
```
If your application doesn't use Symfony Flex you need to manually add your bundle
to the list of registered bundles in `config/bundles.php` file.


Create bundle config
--------------------

```yaml
# config/packages/request_dto_resolver.yaml`
request_dto_resolver:
    target_dto_interface: <target_dto_interface>
```
You need to specify the interface which your target controller argument implements.
See tests for example.
