<?php

declare(strict_types=1);

namespace RequestDtoResolver\Exception;

use Exception;

final class MissingFormTypeAttributeException extends Exception
{
    public function __construct(string $className)
    {
        parent::__construct(sprintf('Missing #[FormType] attribute in class %s', $className));
    }
}
