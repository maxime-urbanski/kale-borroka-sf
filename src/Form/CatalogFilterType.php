<?php

declare(strict_types=1);

namespace App\Form;

use App\Catalog\CatalogFilters;
use App\Catalog\CatalogSort;
use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use App\Enum\ItemCondition;
use App\Enum\SupportType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Reads the catalogue's query string into CatalogFilters. Unnamed (?styles[]=punk, not
 * ?catalog_filter[styles][]=…), entities by slug. The page renders its inputs itself, with
 * the counts: this form only maps and checks what comes back. A value it does not know
 * (a renamed label, a typo) is dropped, the rest still applies.
 */
class CatalogFilterType extends AbstractType
{
    /** A switch is off when missing or sent as one of these (?hideSoldOut=0). */
    private const array OFF = [null, '', '0', 'false'];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supports', EnumType::class, ['class' => SupportType::class, 'multiple' => true, 'expanded' => true])
            ->add('styles', EntityType::class, ['class' => Style::class, 'choice_value' => 'slug', 'multiple' => true, 'expanded' => true])
            ->add('labels', EntityType::class, ['class' => Label::class, 'choice_value' => 'slug', 'multiple' => true, 'expanded' => true])
            ->add('artists', EntityType::class, ['class' => Artist::class, 'choice_value' => 'slug', 'multiple' => true, 'expanded' => true])
            ->add('conditions', EnumType::class, ['class' => ItemCondition::class, 'multiple' => true, 'expanded' => true])
            ->add('kbrProduction', CheckboxType::class, ['required' => false, 'false_values' => self::OFF])
            ->add('hideSoldOut', CheckboxType::class, ['required' => false, 'false_values' => self::OFF])
            ->add('sort', EnumType::class, ['class' => CatalogSort::class, 'empty_data' => CatalogSort::NEW->value])
            ->add('artistSearch', SearchType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CatalogFilters::class,
            'method' => 'GET',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
