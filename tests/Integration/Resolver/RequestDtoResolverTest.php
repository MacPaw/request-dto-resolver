<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Integration\Resolver;

use RequestDtoResolver\Exception\InvalidParamsDtoException;
use RequestDtoResolver\Exception\MissingFormTypeAttributeException;
use RequestDtoResolver\Resolver\RequestDtoResolver;
use RequestDtoResolver\Tests\AbstractKernelTestCase;
use RequestDtoResolver\Tests\Fixture\Controller;
use RequestDtoResolver\Tests\Fixture\TargetDto;
use RequestDtoResolver\Tests\Fixture\TargetDtoAsForm;
use RequestDtoResolver\Tests\Fixture\TargetDtoInterface;
use stdClass;
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
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request();
        $request->attributes->set('_controller', Controller::class);
        $request->request->set('foo', 'abc');
        $request->request->set('bar', 'def');

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertInstanceOf(TargetDtoInterface::class, $resolved[0]);
    }

    public function testInvalidParamsDtoException(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request();
        $request->attributes->set('_controller', Controller::class);
        $request->request->set('foo', 5);
        $request->request->set('bar', false);

        $this->expectException(InvalidParamsDtoException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testMissingFormTypeAttributeException(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $controllerWithoutFormType = new class {
            public function __invoke()
            {
            }
        };

        $request = new Request();
        $request->attributes->set('_controller', get_class($controllerWithoutFormType));

        $this->expectException(MissingFormTypeAttributeException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testReturnsEmptyArrayForNullType(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(null);

        $request = new Request();

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertSame([], $resolved);
    }

    public function testReturnsEmptyArrayForNonTargetDtoInterface(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(stdClass::class);

        $request = new Request();

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertSame([], $resolved);
    }

    public function testParamsFallbackToHeaders(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request();
        $request->attributes->set('_controller', Controller::class);
        $request->request->set('foo', null);
        $request->request->set('bar', 'abc');
        $request->headers->set('foo', 'headerValue');

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertSame('headerValue', $resolved[0]->foo);
    }

    public function testTargetDtoAsForm(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDtoAsForm::class);

        $request = new Request();
        $request->attributes->set('_controller', Controller::class);
        $request->request->set('foo', 'abc');
        $request->request->set('bar', 'def');

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertInstanceOf(TargetDtoInterface::class, $resolved[0]);
    }
}
