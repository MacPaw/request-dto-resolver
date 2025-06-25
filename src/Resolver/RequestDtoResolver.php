<?php

declare(strict_types=1);

namespace RequestDtoResolver\Resolver;

use RequestDtoResolver\Attribute\FormType;
use RequestDtoResolver\Exception\InvalidParamsDtoException;
use RequestDtoResolver\Exception\MissingFormTypeAttributeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use ReflectionClass;

class RequestDtoResolver implements ValueResolverInterface
{
    private const FORMAT_FORM = 'form';
    private const FORMAT_JSON = 'json';
    private const SUPPORTED_FORMATS = [self::FORMAT_JSON, self::FORMAT_FORM];

    public function __construct(
        private FormFactoryInterface $formFactory,
        private DecoderInterface $decoder,
        private string $targetDtoInterface
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $dtoClass = $argument->getType();

        if ($dtoClass === null || !is_subclass_of($dtoClass, $this->targetDtoInterface)) {
            return [];
        }

        $format = $this->resolveFormat($request);
        $formType = $this->resolveFormType($request, $dtoClass);
        $form = $this->formFactory->create($formType);
        $content = $request->getContent();

        if (
            is_string($content)
            && $content !== ''
            && $this->decoder->supportsDecoding($format)
        ) {
            try {
                $data = (array) $this->decoder->decode($content, $format);
            } catch (NotEncodableValueException) {
                $data = [];
            }
        }

        $params = [];
        foreach ($form->all() as $key => $value) {
            $lookupKey = $value->getConfig()->getOption('attr')['lookupKey'] ?? $key;
            $params[$key] = $data[$lookupKey] ?? $request->get($lookupKey);
            if ($params[$key] === null) {
                $params[$key] = $request->headers->get($lookupKey);
            }
        }
        $form->submit($params);

        if (!$form->isValid()) {
            $constraintViolationList = new ConstraintViolationList();

            foreach ($form->getErrors(true) as $error) {
                if ($error->getCause() instanceof ConstraintViolationInterface) {
                    $constraintViolationList->add($error->getCause());
                }
            }

            throw new InvalidParamsDtoException($constraintViolationList, $dtoClass);
        }

        return [$form->getData()];
    }

    private function resolveFormat(Request $request): string
    {
        // If request data is already parsed, use form format
        if (count($request->request->all()) > 0) {
            return self::FORMAT_FORM;
        }

        $contentType = $request->headers->get('Content-Type');

        if (!$contentType) {
            return self::FORMAT_FORM; // fallback
        }

        $format = $request->getFormat($contentType);

        if (!in_array($format, self::SUPPORTED_FORMATS, true)) {
            throw new UnsupportedMediaTypeHttpException(
                sprintf("Unsupported format: %s", $format)
            );
        }

        return $format;
    }

    private function resolveFormType(Request $request, string $dtoClass): string
    {
        if (is_subclass_of($dtoClass, FormTypeInterface::class)) {
            return $dtoClass;
        }

        /** @var string $controllerClass */
        $controllerClass = $request->attributes->get('_controller');

        $reflection = new ReflectionClass($controllerClass);

        $attributes = $reflection->getMethod('__invoke')->getAttributes(FormType::class);

        if (count($attributes) === 0) {
            throw new MissingFormTypeAttributeException($controllerClass);
        }

        /** @var FormType $attribute */
        $attribute = $attributes[0]->newInstance();

        return $attribute->getClass();
    }
}
