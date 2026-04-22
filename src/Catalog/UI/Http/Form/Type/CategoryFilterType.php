<?php

declare(strict_types=1);

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\Application\Search\CategorySearchCriteria;
use App\Catalog\UI\Http\Form\DataTransformer\stringToPriceModelTransformer;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<CategorySearchCriteria>
 */
final class CategoryFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly stringToPriceModelTransformer $stringToPriceModelTransformer,
        private readonly IdToEntityTransformerFactory $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('vatRate', EntityType::class, [
                'label' => 'Vat Rate',
                'class' => VatRate::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Vat Rate',
                'property_path' => 'vatRateId',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Any Price Model',
            ])
            ->add('manager', EntityType::class, [
                'label' => 'Category Manager',
                'class' => User::class,
                'choices' => $this->em->getRepository(User::class)->findBy(['isStaff' => true]),
                'choice_label' => 'fullName',
                'placeholder' => 'Any Manager',
                'property_path' => 'managerId',
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('vatRate')
            ->addModelTransformer($this->transformer->for(VatRate::class));
        $builder->get('priceModel')
            ->addModelTransformer($this->stringToPriceModelTransformer);
        $builder->get('manager')
            ->addModelTransformer($this->transformer->for(User::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategorySearchCriteria::class,
        ]);
    }
}
