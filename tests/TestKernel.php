<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests;

use RequestDtoResolver\RequestDtoResolverBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new RequestDtoResolverBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/Fixture/config/framework.yaml');
        $loader->load(__DIR__ . '/Fixture/config/request_dto_resolver.yaml');
        $loader->load(__DIR__ . '/Fixture/config/services.yaml');
    }
}
