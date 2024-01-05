<?php

declare(strict_types=1);

namespace App\Form;

use App\Data\ArticleFilterData;
use App\Entity\Support;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductionFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('globalFilters', GlobalArticleFilterType::class)
            ->add('supports', EntityType::class, [
                'label' => 'Support',
                'label_attr' => [
                    'class' => 'text-uppercase fs-6',
                ],
                'class' => Support::class,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'text-uppercase',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn btn-outline-dark',
                ],
                'label' => 'Rechercher',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleFilterData::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
