# Symfony Request DTO Resolver Bundle

Automatically resolves and validates Symfony HTTP request data (JSON, form-data, query parameters) into DTOs.

## Features

- Automatic resolution of request data into DTOs.
- Seamless support for JSON, form-data, and query string parameters.
- Built-in validation using the Symfony Form component.
- Support for complex nested data structures.
- Customizable parameter resolution order and field mapping.
- Smart integration with other bundles that parse the request body.

## Installation

```console
composer require macpaw/request-dto-resolver
```

The bundle should be automatically registered in your `config/bundles.php`. If not, add it manually:

```php
// config/bundles.php
return [
    RequestDtoResolver\RequestDtoResolverBundle::class => ['all' => true],
    // ...
];
```

## Configuration

First, define an interface that your DTOs will implement. This allows the resolver to identify which arguments to process.

```php
// src/DTO/RequestDtoInterface.php
namespace App\DTO;

interface RequestDtoInterface
{
}
```

Then, point the bundle to this interface in a configuration file:

```yaml
# config/packages/request_dto_resolver.yaml
request_dto_resolver:
    target_dto_interface: App\DTO\RequestDtoInterface
```

## How It Works

The resolver uses a combination of a DTO class and a Symfony Form to process and validate incoming request data.

1.  **Controller Argument**: You type-hint a controller argument with your DTO class (e.g., `UserDto`).
2.  **FormType Attribute**: You decorate the controller action with the `#[FormType]` attribute, specifying which Symfony Form to use for processing.
3.  **Data Resolution**: The resolver extracts data from the request based on the form's fields.
4.  **Validation**: The form validates the data against the constraints defined in your DTO.
5.  **DTO Hydration**: If validation passes, a new DTO instance is created and populated with the validated data.

## Usage

### 1. Create a DTO

The DTO is a simple PHP class that implements your marker interface. Use Symfony's Validator components to define constraints.

```php
// src/DTO/UserDto.php
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

The Form Type defines the structure of the expected request data and maps it to your DTO.

```php
// src/Form/UserFormType.php
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
                'allow_add' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserDto::class,
        ]);
    }
}
```

### 3. Use in a Controller

In your controller, type-hint the action argument with your DTO class and add the `#[FormType]` attribute.

```php
// src/Controller/UserController.php
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
        // $dto is now a validated and populated object
        return new JsonResponse([
            'name' => $dto->name,
            'email' => $dto->email,
            'tags' => $dto->tags,
        ]);
    }
}
```

## Parameter Resolution

The resolver automatically extracts data from the request to populate the form. The source of the data depends on the request's `Content-Type` header and method.

### Resolution Order

For each field defined in your Form Type, the resolver searches for a corresponding value in the following order:

1.  **JSON Body**: If the request has a `Content-Type` of `application/json`, the decoded JSON body is checked first.
2.  **Query & Form Data**: The resolver then checks `request->query` (for `GET` parameters) and `request->request` (for `POST` form data).
3.  **Request Headers**: Finally, it checks the request headers.

This order means that for a `POST` request with both a JSON body and query parameters, the values in the **JSON body will take precedence**.

### Common Scenarios

-   **POST with JSON Body**: `{"name": "John"}` -> `name` is resolved from JSON.
-   **POST with Form Data**: `name=John` -> `name` is resolved from form data.
-   **GET with Query Parameters**: `?name=John` -> `name` is resolved from query string.
-   **GET with `Content-Type: application/json`**: The resolver will correctly ignore the header and still pull data from the query string, preventing malformed body errors.
-   **Request without `Content-Type`**: The request is treated as a standard form/query request, and data is resolved from the query string.

## Advanced Features

### Custom Field Mapping

You can map request fields to different DTO properties using the `lookupKey` option in your Form Type. This is useful for handling request keys that don't match your DTO property names (e.g., `user-id` vs. `userId`).

**Form Type Configuration:**

```php
// ...
$builder->add('userId', TextType::class, [
    'attr' => ['lookupKey' => 'user-id'],
]);
// ...
```

This configuration will map the `user-id` key from any source (JSON body, query, or header) to the `userId` form field.

**Request Example:**

```http
POST /api/some-endpoint
Content-Type: application/json

{
    "user-id": 123
}
```

## Integration with Other Bundles

This bundle is designed to work seamlessly with other bundles that parse the request body (e.g., FOSRestBundle). If the request body is already parsed and populated in `$request->request`, the resolver will automatically use this pre-parsed data instead of reading the raw body again.

This ensures:
-   No double-parsing of the request body.
-   Consistent validation and mapping rules.
-   Zero-configuration interoperability.

## Error Handling

The bundle throws the following exceptions, which you can handle with a standard Symfony exception listener:

-   `InvalidParamsDtoException`: For validation errors (contains a `ConstraintViolationList`).
-   `BadRequestHttpException`: For a malformed JSON body.
-   `UnsupportedMediaTypeHttpException`: For an unsupported `Content-Type`.
-   `MissingFormTypeAttributeException`: When the `#[FormType]` attribute is missing on the controller action.

## Contributing

Feel free to open issues and submit pull requests.

## License

This bundle is released under the MIT license.
