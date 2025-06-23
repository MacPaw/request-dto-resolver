<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use RequestDtoResolver\Attribute\FormType;

class ComplexController
{
    #[FormType(class: ComplexForm::class)]
    public function __invoke()
    {
    }
}
