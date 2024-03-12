<?php

namespace App\Form;

use App\Entity\ProductImage;
use App\Form\DataTransformer\ProductToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductImageType extends AbstractType
{
    public function __construct(private readonly ProductToIdTransformer $productToIdTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('product', TextType::class, [
                'label' => 'Product Id',
                'invalid_message' => 'Product not found',
            ])
            ->add('imageFile', FileType::class, [
                'required' => true,
                'label' => 'Image',
            ])
        ;

        $builder->get('product')->addModelTransformer($this->productToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductImage::class,
        ]);
    }
}
