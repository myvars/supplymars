# Symfony Forms

Forms handle all data input with server-side validation. The architecture separates concerns across Form Types, Form Models (DTOs), and Mappers.

## Architecture

```
src/{Context}/UI/Http/Form/
├── Type/                 # Form type classes
│   └── ProductType.php
├── Model/                # Form DTOs
│   └── ProductForm.php
├── Mapper/               # DTO → Command conversion
│   └── CreateProductMapper.php
└── DataTransformer/      # Value transformation
    └── IdToEntityTransformer.php
```

## Form Flow

```
┌─────────────────────────────────────────────────────────────────┐
│  1. Controller receives request                                 │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  2. FormFlow creates form with FormType + FormModel             │
│     $form = createForm(ProductType::class, new ProductForm())   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  3. Form handles request                                        │
│     $form->handleRequest($request)                              │
└─────────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              ▼                               ▼
┌─────────────────────────┐    ┌─────────────────────────────────┐
│  Invalid: Re-render     │    │  Valid: Map to Command          │
│  form with errors       │    │  $command = $mapper($formData)  │
│  Return 422             │    └─────────────────────────────────┘
└─────────────────────────┘                   │
                                              ▼
                           ┌─────────────────────────────────────┐
                           │  4. Handler executes command        │
                           │     Returns Result::ok() or fail()  │
                           └─────────────────────────────────────┘
                                              │
                                              ▼
                           ┌─────────────────────────────────────┐
                           │  5. Redirect on success             │
                           │     Or re-render on handler failure │
                           └─────────────────────────────────────┘
```

## Form Type

Form types define the form structure and field configuration:

```php
<?php
// src/Catalog/UI/Http/Form/Type/ProductType.php

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\UI\Http\Form\Model\ProductForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ProductForm>
 */
final class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Product Name',
                'attr' => ['placeholder' => 'Enter product name'],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select category',
            ])
            ->add('price', MoneyType::class, [
                'currency' => 'GBP',
                'divisor' => 100,  // Store as pence
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductForm::class,
        ]);
    }
}
```

## Form Model (DTO)

Form models are simple DTOs that hold form data:

```php
<?php
// src/Catalog/UI/Http/Form/Model/ProductForm.php

namespace App\Catalog\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Product\Product;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductForm
{
    public ?int $id = null;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    public string $name = '';

    #[Assert\NotNull]
    public ?int $categoryId = null;

    #[Assert\Positive]
    public ?int $price = null;

    /**
     * Factory for update forms
     */
    public static function fromEntity(Product $product): self
    {
        $form = new self();
        $form->id = $product->getId();
        $form->name = $product->getName();
        $form->categoryId = $product->getCategory()->getId();
        $form->price = $product->getPrice();

        return $form;
    }
}
```

## Mapper

Mappers convert form DTOs to commands:

```php
<?php
// src/Catalog/UI/Http/Form/Mapper/CreateProductMapper.php

namespace App\Catalog\UI\Http\Form\Mapper;

use App\Catalog\Application\Command\Product\CreateProduct;
use App\Catalog\UI\Http\Form\Model\ProductForm;
use App\Shared\Domain\ValueObject\CategoryId;

final class CreateProductMapper
{
    public function __invoke(ProductForm $data): CreateProduct
    {
        return new CreateProduct(
            name: $data->name,
            categoryId: CategoryId::fromInt($data->categoryId),
            price: $data->price,
        );
    }
}
```

```php
// Update mapper includes the ID
final class UpdateProductMapper
{
    public function __invoke(ProductForm $data): UpdateProduct
    {
        return new UpdateProduct(
            id: ProductPublicId::fromString($data->id),
            name: $data->name,
            categoryId: CategoryId::fromInt($data->categoryId),
            price: $data->price,
        );
    }
}
```

## Controller Integration

Controllers use FormFlow for consistent handling:

```php
#[Route('/catalog/product/new', methods: ['GET', 'POST'])]
public function new(
    Request $request,
    CreateProductMapper $mapper,
    CreateProductHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ProductType::class,
        data: new ProductForm(),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forCreate('catalog/product'),
    );
}

#[Route('/catalog/product/{id}/edit', methods: ['GET', 'POST'])]
public function edit(
    Request $request,
    #[ValueResolver('public_id')] Product $product,
    UpdateProductMapper $mapper,
    UpdateProductHandler $handler,
    FormFlow $flow,
): Response {
    return $flow->form(
        request: $request,
        formType: ProductType::class,
        data: ProductForm::fromEntity($product),
        mapper: $mapper,
        handler: $handler,
        context: FlowContext::forUpdate('catalog/product')
            ->allowDelete(true)
            ->successRoute('app_catalog_product_show', ['id' => $product->publicId]),
    );
}
```

## Dynamic Forms

Use `symfonycasts/dynamic-forms` for dependent fields:

```php
use Symfonycasts\DynamicForms\DynamicFormBuilder;

public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder = new DynamicFormBuilder($builder);

    $builder
        ->add('category', EntityType::class, [
            'class' => Category::class,
            'choice_label' => 'name',
        ])
        ->addDependent('subcategory', 'category', function (DependentField $field, ?Category $category) {
            if (!$category) {
                $field->add(ChoiceType::class, [
                    'choices' => [],
                    'placeholder' => 'Select category first',
                ]);
                return;
            }

            $field->add(EntityType::class, [
                'class' => Subcategory::class,
                'query_builder' => fn (SubcategoryRepository $repo) =>
                    $repo->createQueryBuilder('s')
                        ->where('s.category = :category')
                        ->setParameter('category', $category),
                'choice_label' => 'name',
            ]);
        });
}
```

## Stimulus Integration

Add Stimulus attributes for enhanced behavior:

```php
$builder->add('category', EntityType::class, [
    'class' => Category::class,
    'attr' => [
        // Trigger form resubmit on change (for dependent fields)
        'data-action' => 'change->submit-form#submitForm',
    ],
]);

$builder->add('search', SearchType::class, [
    'attr' => [
        // Connect to searchbox controller
        'data-searchbox-target' => 'queryInput',
        'data-action' => 'input->searchbox#debouncedQueryInputChanged',
    ],
]);
```

## Form Themes

Forms use the Flowbite theme with custom overrides:

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - '@TalesFromADevFlowbite/form/default.html.twig'
        - 'bundles/TalesFromADevFlowbiteBundle/form/custom_form_theme.html.twig'
```

Custom theme for minor adjustments:

```twig
{# templates/bundles/TalesFromADevFlowbiteBundle/form/custom_form_theme.html.twig #}
{% use '@TalesFromADevFlowbite/form/default.html.twig' %}

{% block choice_widget_collapsed %}
    {% set attr = attr|merge({class: (attr.class|default('') ~ ' h-10')|trim}) %}
    {{ parent() }}
{% endblock %}
```

## Data Transformers

Transform data between form and model representations:

```php
<?php
// src/Shared/UI/Http/Form/DataTransformer/IdToEntityTransformer.php

use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<Entity, string>
 */
final class IdToEntityTransformer implements DataTransformerInterface
{
    public function __construct(
        private EntityRepository $repository,
    ) {}

    public function transform(mixed $value): string
    {
        if (!$value instanceof Entity) {
            return '';
        }

        return $value->getPublicId()->value();
    }

    public function reverseTransform(mixed $value): ?Entity
    {
        if (!$value) {
            return null;
        }

        return $this->repository->findByPublicId(
            EntityPublicId::fromString($value)
        );
    }
}
```

Usage in form type:

```php
$builder->add('product', HiddenType::class);
$builder->get('product')->addModelTransformer($this->productTransformer);
```

## Validation

Validation uses Symfony Validator constraints on the form model:

```php
use Symfony\Component\Validator\Constraints as Assert;

final class ProductForm
{
    #[Assert\NotBlank(message: 'Product name is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Name must be at least {{ limit }} characters',
    )]
    public string $name = '';

    #[Assert\NotNull(message: 'Please select a category')]
    public ?int $categoryId = null;

    #[Assert\Positive(message: 'Price must be positive')]
    #[Assert\LessThan(value: 1000000, message: 'Price too high')]
    public ?int $price = null;

    #[Assert\Valid]  // Validate nested objects
    public ?AddressForm $address = null;
}
```

## Best Practices

1. **Separate form models from entities** - Forms have their own DTOs

2. **Use mappers for transformation** - Keep forms focused on presentation

3. **Add validation to form models** - Not entities

4. **Use EntityType for relationships** - Let Symfony handle the query

5. **Configure placeholder text** - Guide users with helpful placeholders

6. **Use dynamic forms for dependencies** - Cleaner than JavaScript

7. **Add Stimulus for enhancement** - Auto-submit, dependent fields

8. **Test forms independently** - Form types are unit testable
