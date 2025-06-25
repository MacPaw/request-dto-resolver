<?php

declare(strict_types=1);

namespace RequestDtoResolver\Attribute;

use Attribute;

#[Attribute]
class FormType
{
    public function __construct(
        private string $class = '',
    ) {
    }

    public function getClass(): string
    {
        return $this->class;
    }
}
