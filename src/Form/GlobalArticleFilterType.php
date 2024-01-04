<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Artist;
use App\Entity\Label;
use App\Entity\Style;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class GlobalArticleFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('artists', EntityType::class, [
                'label' => 'Artistes',
                'label_attr' => [
                    'class' => 'text-uppercase fs-6',
                ],
                'class' => Artist::class,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'text-uppercase',
                ],
            ])
            ->add('labels', EntityType::class, [
                'label' => 'Label',
                'label_attr' => [
                    'class' => 'text-uppercase fs-6',
                ],
                'class' => Label::class,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'text-uppercase',
                ],
            ])
            ->add('styles', EntityType::class, [
                'label' => 'Styles',
                'label_attr' => [
                    'class' => 'text-uppercase fs-6',
                ],
                'class' => Style::class,
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'attr' => [
                    'class' => 'text-uppercase',
                ],
            ])
            ->add('kbrProduction', CheckboxType::class, [
                'label' => 'Nos productions',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
                'value' => false,
            ]);
    }
}
