<?php

declare(strict_types=1);

namespace RequestDtoResolver\Exception;

use Exception;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class InvalidParamsException extends Exception
{
    private readonly ConstraintViolationListInterface $list;

    public function __construct(ConstraintViolationListInterface $list)
    {
        $this->list = $list;

        parent::__construct('Params not valid');
    }

    public function getList(): ConstraintViolationListInterface
    {
        return $this->list;
    }
}
