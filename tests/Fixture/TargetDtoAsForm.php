<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TargetDtoAsForm extends Form implements TargetDtoInterface
{
    public string $foo;

    public string $bar;

    public string $baz;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => static::class
        ]);
    }
}
