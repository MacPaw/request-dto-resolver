<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Integration\Resolver;

use RequestDtoResolver\Exception\InvalidParamsDtoException;
use RequestDtoResolver\Resolver\RequestDtoResolver;
use RequestDtoResolver\Tests\AbstractKernelTestCase;
use RequestDtoResolver\Tests\Fixture\ComplexController;
use RequestDtoResolver\Tests\Fixture\ComplexDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class ComplexRequestDtoResolverTest extends AbstractKernelTestCase
{
    private RequestDtoResolver $requestDtoResolver;

    protected function setUp(): void
    {
        $this->requestDtoResolver = self::getContainer()->get(RequestDtoResolver::class);
    }

    public function testResolveValidJsonRequest(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(ComplexDto::class);

        $jsonData = json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'tags' => ['developer', 'php']
        ]);

        $request = new Request(
            attributes: ['_controller' => ComplexController::class],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $jsonData
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);
        $dto = $resolved[0];

        $this->assertInstanceOf(ComplexDto::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals('john@example.com', $dto->email);
        $this->assertEquals(25, $dto->age);
        $this->assertEquals(['developer', 'php'], $dto->tags);
    }

    public function testInvalidJsonDataValidation(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(ComplexDto::class);

        $jsonData = json_encode([
            'name' => 'Jo', // too short
            'email' => 'not-an-email',
            'age' => 15, // too young
            'tags' => [] // empty array
        ]);

        $request = new Request(
            attributes: ['_controller' => ComplexController::class],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $jsonData
        );

        $this->expectException(InvalidParamsDtoException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testUnsupportedMediaType(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(ComplexDto::class);

        $request = new Request(
            attributes: ['_controller' => ComplexController::class],
            server: ['CONTENT_TYPE' => 'application/yaml']
        );

        $this->expectException(UnsupportedMediaTypeHttpException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testNestedArrayValidation(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(ComplexDto::class);

        $jsonData = json_encode([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25,
            'tags' => ['', 'a'] // First tag empty, second too short
        ]);

        $request = new Request(
            attributes: ['_controller' => ComplexController::class],
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $jsonData
        );

        $this->expectException(InvalidParamsDtoException::class);
        $this->requestDtoResolver->resolve($request, $argumentMock);
    }

    public function testFormDataWithNestedArray(): void
    {
        $argumentMock = $this->createMock(ArgumentMetadata::class);
        $argumentMock->method('getType')->willReturn(ComplexDto::class);

        $request = new Request(
            request: [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => '25',
                'tags' => ['developer', 'php']
            ],
            attributes: ['_controller' => ComplexController::class]
        );

        $resolved = $this->requestDtoResolver->resolve($request, $argumentMock);
        $dto = $resolved[0];

        $this->assertInstanceOf(ComplexDto::class, $dto);
        $this->assertEquals('John Doe', $dto->name);
        $this->assertEquals(['developer', 'php'], $dto->tags);
    }
}
