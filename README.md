# Symfony Request DTO Resolver Bundle

Automatically resolves Symfony HTTP request data into DTOs with validation support. Handles both JSON and form-data requests seamlessly.

## Features

- Automatic request data resolution into DTOs
- Support for both JSON and form-data requests
- Built-in validation using Symfony Forms
- Nested data structures support
- Fallback to request headers
- Custom field mapping via lookup keys

## Installation

```console
composer require macpaw/request-dto-resolver
```

The bundle should be automatically registered in your `config/bundles.php`:

```php
return [
    RequestDtoResolver\RequestDtoResolverBundle::class => ['all' => true],
    // ...
];
```

If your application doesn't use Symfony Flex, add the bundle manually.

## Configuration

Create the configuration file:

```yaml
# config/packages/request_dto_resolver.yaml
request_dto_resolver:
    target_dto_interface: App\DTO\RequestDtoInterface
```

Create the DTO interface:

```php
namespace App\DTO;

interface RequestDtoInterface
{
}
```

## Usage

### 1. Create a DTO

```php
namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDto implements RequestDtoInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    /** @var string[] */
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\NotBlank,
        new Assert\Length(min: 2)
    ])]
    public array $tags = [];
}
```

### 2. Create a Form Type

```php
namespace App\Form;

use App\DTO\UserDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->add('tags', CollectionType::class, [
                'entry_type' => TextType::class,
                'allow_add' => true
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDto::class
        ]);
    }
}
```

### 3. Create a Controller

```php
namespace App\Controller;

use App\DTO\UserDto;
use App\Form\UserFormType;
use RequestDtoResolver\Attribute\FormType;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController
{
    #[FormType(UserFormType::class)]
    public function __invoke(UserDto $dto): JsonResponse
    {
        return new JsonResponse([
            'name' => $dto->name,
            'email' => $dto->email,
            'tags' => $dto->tags
        ]);
    }
}
```

## Request Examples

### JSON Request

```http
POST /api/user
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "tags": ["developer", "php"]
}
```

### Form Data Request

```http
POST /api/user
Content-Type: application/x-www-form-urlencoded

name=John+Doe&email=john@example.com&tags[]=developer&tags[]=php
```

## Advanced Features

### Custom Field Mapping

You can map request fields to different DTO properties using the `lookupKey` option:

```php
public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder->add('userId', TextType::class, [
        'attr' => ['lookupKey' => 'user-id']
    ]);
}
```

This will map the `user-id` request field to the `userId` DTO property.

### Header Fallback

If a field is not found in the request data, the resolver will look for it in the request headers:

```http
POST /api/user
Content-Type: application/json
X-User-Id: 12345

{
    "name": "John Doe"
}
```

## Error Handling

The bundle throws:

- `InvalidParamsDtoException` for validation errors
- `BadRequestHttpException` for malformed request body
- `UnsupportedMediaTypeHttpException` for unsupported content types
- `MissingFormTypeAttributeException` when the FormType attribute is missing

## Contributing

Feel free to open issues and submit pull requests.

## License

This bundle is released under the MIT license.
