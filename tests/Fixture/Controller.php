<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use RequestDtoResolver\Attribute\FormType;

class Controller
{
    #[FormType(class: Form::class)]
    public function __invoke()
    {
    }
}
