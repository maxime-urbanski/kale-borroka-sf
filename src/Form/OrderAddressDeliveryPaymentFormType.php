<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\Payment;
use App\Entity\Transporter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAddressDeliveryPaymentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $builder
            ->add('address', EntityType::class, [
                'label' => 'Adresse de livraison',
                'class' => Address::class,
                'choices' => $user->getAddresses(),
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('transport', EntityType::class, [
                'label' => 'Choix du transporteur',
                'class' => Transporter::class,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('payment', EntityType::class, [
                'label' => 'Methode de paiement',
                'class' => Payment::class,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'btn-outline-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
        ]);
    }
}
