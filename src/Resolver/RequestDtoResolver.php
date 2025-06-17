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
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use ReflectionClass;

readonly class RequestDtoResolver implements ValueResolverInterface
{
    private const SUPPORTED_FORMATS = ['json', 'form'];

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

        if ($format === 'form' || !empty($request->request->all())) {
            $data = [];
            foreach ($form->all() as $key => $value) {
                $lookupKey = $value->getConfig()->getOption('attr')['lookupKey'] ?? $key;
                $data[$key] = $request->get($lookupKey);
                if ($data[$key] === null) {
                    $data[$key] = $request->headers->get($lookupKey);
                }
            }
            $form->submit($data);
        } else {
            try {
                $data = $this->decoder->decode($request->getContent(), $format);
                $form->submit($data);
            } catch (NotEncodableValueException $e) {
                throw new BadRequestHttpException('Malformed request body.');
            }
        }

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
        if (!empty($request->request->all())) {
            return 'form';
        }

        $contentType = $request->headers->get('Content-Type');

        if (!$contentType) {
            return 'form'; // fallback
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
