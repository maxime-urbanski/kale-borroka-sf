<?php

namespace App\Form;

use App\Data\ArticleFilterData;
use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleFilterFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artists',EntityType::class, [
                'label' => 'Artistes',
                'class' => Artist::class,
                'required' => false,
                'multiple' => true
            ])
            ->add('labels',EntityType::class, [
                'label' => 'Artistes',
                'class' => Label::class,
                'required' => false,
                'multiple' => true
            ])
            ->add('styles',EntityType::class, [
                'label' => 'Artistes',
                'class' => Style::class,
                'required' => false,
                'multiple' => true
            ])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ArticleFilterData::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
