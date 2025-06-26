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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

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
        $request->request->set('Baz-key', 'ghi');

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
        $request->request->set('Baz-key', false);

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
        $request->headers->set('Baz-key', 'bazHeaderValue');

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
        $request->request->set('Baz-key', 'ghi');

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertInstanceOf(TargetDtoInterface::class, $resolved[0]);
    }

    public function testThrowsExceptionOnInvalidJson(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $invalidJsonData = '{"foo": "bar",}';

        $request = new Request(
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            content: $invalidJsonData
        );

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid JSON format');

        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testEmptyJsonBodyTriggersFormValidation(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request(
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            content: ''
        );

        $this->expectException(InvalidParamsDtoException::class);

        try {
            $this->requestDtoResolver->resolve($request, $argumentMock);
        } catch (InvalidParamsDtoException $e) {
            $this->assertSame(TargetDto::class, $e->getDtoClassName());
            $this->assertGreaterThan(0, $e->getList()->count());
            throw $e;
        }
    }

    public function testLookupKeyWorksWithJsonRequest(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $jsonData = json_encode([
            'foo' => 'json_foo',
            'bar' => 'json_bar',
            'Baz-key' => 'value_from_lookup_key',
        ]);

        $request = new Request(
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            content: $jsonData
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);
        $dto = $resolved[0];

        $this->assertInstanceOf(TargetDto::class, $dto);
        $this->assertSame('value_from_lookup_key', $dto->baz);
    }

    public function testResolvesGetRequestWithJsonContentTypeAndQueryParameters(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request(
            query: ['foo' => 'abc', 'bar' => 'def', 'Baz-key' => 'ghi'],
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'GET', 'CONTENT_TYPE' => 'application/json']
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertCount(1, $resolved);
        $this->assertInstanceOf(TargetDto::class, $resolved[0]);
        /** @var TargetDto $dto */
        $dto = $resolved[0];
        $this->assertSame('abc', $dto->foo);
        $this->assertSame('def', $dto->bar);
        $this->assertSame('ghi', $dto->baz);
    }

    public function testJsonBodyTakesPrecedenceOverQuery(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $jsonData = json_encode([
            'foo' => 'from_body',
            'bar' => 'from_body',
        ]);

        $request = new Request(
            query: ['foo' => 'from_query', 'Baz-key' => 'from_query'],
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'POST', 'CONTENT_TYPE' => 'application/json'],
            content: $jsonData
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertCount(1, $resolved);
        /** @var TargetDto $dto */
        $dto = $resolved[0];
        $this->assertSame('from_body', $dto->foo);
        $this->assertSame('from_body', $dto->bar);
        $this->assertSame('from_query', $dto->baz);
    }

    public function testResolvesWithQueryParametersAndNoContentType(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(TargetDto::class);

        $request = new Request(
            query: ['foo' => 'abc', 'bar' => 'def', 'Baz-key' => 'ghi'],
            attributes: ['_controller' => Controller::class],
            server: ['REQUEST_METHOD' => 'POST']
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);

        $this->assertCount(1, $resolved);
        /** @var TargetDto $dto */
        $dto = $resolved[0];
        $this->assertSame('abc', $dto->foo);
        $this->assertSame('def', $dto->bar);
        $this->assertSame('ghi', $dto->baz);
    }
}
