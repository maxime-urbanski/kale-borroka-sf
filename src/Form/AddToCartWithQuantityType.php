<?php

namespace App\Form;

use App\Data\AddToCartWithQuantity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddToCartWithQuantityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AddToCartWithQuantity $data */
        $data = $options['data'];

        $builder
            ->add('less', ButtonType::class, [
                'label' => '<i class="bi bi-dash-lg"></i>',
                'label_html' => true,
                'attr' => [
                    'class' => 'btn btn-outline-danger',
                    'data-action' => 'click->add-to-cart#less',
                ],
            ])
            ->add('quantity', NumberType::class, [
                'attr' => [
                    'class' => 'form-control flex-grow-0 rounded-0 text-center',
                    'data-add-to-cart-target' => 'input',
                    'style' => 'width: 50px',
                    'value' => 1,
                    'max' => $data->quantityAvailable,
                ],
                'label' => 'Quantité',
            ])
            ->add('more', ButtonType::class, [
                'label' => '<i class="bi bi-plus-lg"></i>',
                'label_html' => true,
                'attr' => [
                    'class' => 'btn btn-outline-success',
                    'data-action' => 'click->add-to-cart#more ',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddToCartWithQuantity::class,
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }
}
