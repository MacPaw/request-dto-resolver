<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use RequestDtoResolver\DependencyInjection\RequestDtoResolverExtension;
use RequestDtoResolver\Tests\Fixture\TargetDtoInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RequestDtoResolverExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $configs = [
            'request_dto_resolver' => [
                'target_dto_interface' => 'RequestDtoResolver\Tests\Fixture\TargetDtoInterface',
            ],
        ];

        $container = new ContainerBuilder();
        $extension = new RequestDtoResolverExtension();
        $extension->load($configs, $container);

        $this->assertTrue($container->hasParameter('request_dto_resolver.target_dto_interface'));
        $this->assertEquals(TargetDtoInterface::class, $container->getParameter(
            'request_dto_resolver.target_dto_interface'
        ));
    }
}
