<?php

declare(strict_types=1);

namespace RequestDtoResolver\Tests\Fixture;

use Symfony\Component\Validator\Constraints as Assert;

class ComplexDto implements TargetDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Range(min: 18, max: 150)]
    public int $age;

    /** @var string[] */
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Length(min: 2)
    ])]
    public array $tags = [];
}
