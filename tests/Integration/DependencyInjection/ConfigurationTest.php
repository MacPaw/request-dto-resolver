<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Integration\DependencyInjection;

use PHPUnit\Framework\TestCase;
use RequestDtoResolver\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testConfiguration(): void
    {
        $expectedConfig = [
            'target_dto_interface' => 'RequestDtoResolver\Tests\Fixture\TargetDtoInterface',
        ];

        $processor = new Processor();
        $configs = $processor->processConfiguration(new Configuration(), [
            'request_dto_resolver' => $expectedConfig,
        ]);

        $this->assertSame($expectedConfig, $configs);
    }
}
