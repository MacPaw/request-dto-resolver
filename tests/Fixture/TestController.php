<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use RequestDtoResolver\Attribute\FormType;

class TestController
{
    #[FormType(class: TestForm::class)]
    public function __invoke()
    {
    }
}
