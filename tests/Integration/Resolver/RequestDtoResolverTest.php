<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Integration\Resolver;

use RequestDtoResolver\Exception\InvalidParamsDtoException;
use RequestDtoResolver\Tests\Fixture\TestDto;
use RequestDtoResolver\Tests\Fixture\TargetDtoInterface;
use RequestDtoResolver\Resolver\RequestDtoResolver;
use RequestDtoResolver\Tests\AbstractKernelTestCase;
use RequestDtoResolver\Tests\Fixture\TestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class RequestDtoResolverTest extends AbstractKernelTestCase
{
    private RequestDtoResolver $requestDtoResolver;

    public function setUp(): void
    {
        $this->requestDtoResolver = self::getContainer()->get(RequestDtoResolver::class);
    }

    public function testResolve(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TestDto::class);

        $request = new Request();
        $request->attributes->set('_controller', TestController::class);
        $request->request->set('foo', 'abc');
        $request->request->set('bar', 'def');

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertInstanceOf(TargetDtoInterface::class, $resolved[0]);
    }

    public function testResolveException(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TestDto::class);

        $request = new Request();
        $request->attributes->set('_controller', TestController::class);
        $request->request->set('foo', 5);
        $request->request->set('bar', false);

        $this->expectException(InvalidParamsDtoException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }
}
