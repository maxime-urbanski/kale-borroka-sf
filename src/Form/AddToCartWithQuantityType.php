<?php

declare(strict_types=1);

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
                'label' => '<i class="bi bi-dash-lg" aria-hidden="true"></i>',
                'label_html' => true,
                'attr' => [
                    'class' => 'btn',
                    'aria-label' => 'Diminuer la quantité',
                    'data-action' => 'click->add-to-cart#less',
                ],
            ])
            ->add('quantity', NumberType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'aria-label' => 'Quantité',
                    'data-add-to-cart-target' => 'input',
                    'value' => 1,
                    'max' => $data->quantityAvailable,
                ],
                'label' => 'Quantité',
            ])
            ->add('more', ButtonType::class, [
                'label' => '<i class="bi bi-plus-lg" aria-hidden="true"></i>',
                'label_html' => true,
                'attr' => [
                    'class' => 'btn',
                    'aria-label' => 'Augmenter la quantité',
                    'data-action' => 'click->add-to-cart#more',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AddToCartWithQuantity::class,
            // Changes the cart: POST with the form's CSRF token, never a link another site can trigger.
            'method' => 'POST',
        ]);
    }
}
