<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class Form extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('foo', TextType::class, [
                'required' => true,
                'invalid_message' => 'invalidFoo',
                'constraints' => [
                    new Assert\NotBlank(message: 'notBlank'),
                ]
            ])
            ->add('bar', TextType::class, [
                'required' => true,
                'invalid_message' => 'invalidBar',
                'constraints' => [
                    new Assert\NotBlank(message: 'notBlank'),
                ]
            ])
            ->add('baz', TextType::class, [
                'required' => true,
                'invalid_message' => 'invalidBaz',
                'constraints' => [
                    new Assert\NotBlank(message: 'notBlank'),
                ],
                'attr' => [
                    'lookupKey' => 'Baz-key'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TargetDto::class
        ]);
    }
}
