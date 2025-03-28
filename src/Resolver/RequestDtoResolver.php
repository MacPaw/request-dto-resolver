<?php

declare(strict_types=1);

namespace RequestDtoResolver\Resolver;

use ReflectionClass;
use RequestDtoResolver\Attribute\FormType;
use RequestDtoResolver\Exception\InvalidParamsDtoException;
use RequestDtoResolver\Exception\MissingFormTypeAttributeException;
use Symfony\Component\Form\AbstractType as FormAbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class RequestDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private FormFactoryInterface $formFactory,
        private string $targetDtoInterface,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $dtoClass = $argument->getType();

        if ($dtoClass === null || !is_subclass_of($dtoClass, $this->targetDtoInterface)) {
            return [];
        }

        $formType = is_subclass_of($dtoClass, FormAbstractType::class)
            ? $dtoClass
            : $this->getFormTypeFromReflection($request);

        $form = $this->formFactory->create($formType);

        $params = [];
        foreach ($form->all() as $key => $value) {
            $lookupKey = $value->getConfig()->getOption('attr')['lookupKey'] ?? $key;
            $params[$key] = $request->get($lookupKey);
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

    private function getFormTypeFromReflection(Request $request): string
    {
        /** @var string $controllerClass */
        $controllerClass = $request->attributes->get('_controller');

        $reflection = new ReflectionClass($controllerClass);

        $attributes = $reflection->getMethod('__invoke')->getAttributes(FormType::class);

        if (count($attributes) <= 0) {
            throw new MissingFormTypeAttributeException($controllerClass);
        }

        /** @var FormType $attribute */
        $attribute = $attributes[0]->newInstance();

        return $attribute->getClass();
    }
}
