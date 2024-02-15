<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

abstract class AbstractKernelTestCase extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected static function getContainer(): Container
    {
        if (!self::$booted) {
            self::bootKernel();
        }

        /** @var Container $container */
        $container = self::$kernel->getContainer()->get('test.service_container');

        return $container;
    }
}
