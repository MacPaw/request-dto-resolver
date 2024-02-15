<?php

declare(strict_types=1);

namespace RequestDtoResolver\Resolver;

use Exception;
use ReflectionClass;
use RequestDtoResolver\Attribute\FormType;
use RequestDtoResolver\Exception\InvalidParamsDtoException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class RequestDtoResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly string $targetDtoInterface,
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $dtoClass = $argument->getType();

        if ($dtoClass === null || !is_subclass_of($dtoClass, $this->targetDtoInterface)) {
            return [];
        }

        /** @var string $controllerClass */
        $controllerClass = $request->attributes->get('_controller');
        $formType = $this->getFormType($controllerClass);

        $form = $this->formFactory->create($formType);

        $params = [];
        foreach ($form->all() as $key => $value) {
            $params[$key] = $request->get($key);
            if ($params[$key] === null) {
                $params[$key] = $request->headers->get($key);
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

    private function getFormType(string $controllerClass): string
    {
        $reflection = new ReflectionClass($controllerClass);

        $attributes = $reflection->getMethod('__invoke')->getAttributes(FormType::class);

        if (count($attributes) <= 0) {
            throw new Exception('No FormType argument is specified for controller method');
        }

        /** @var FormType $attribute */
        $attribute = $attributes[0]->newInstance();

        return $attribute->getClass();
    }
}
