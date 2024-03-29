<?php

declare(strict_types=1);

namespace App\Form;

use App\Data\ArticleFilterData;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('globalFilters', GlobalArticleFilterType::class);
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
