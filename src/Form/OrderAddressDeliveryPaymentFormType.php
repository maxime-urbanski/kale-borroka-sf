<?php

declare(strict_types=1);

namespace App\Form;

use App\Data\OrderDeliveryDto;
use App\Entity\Payment;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderAddressDeliveryPaymentFormType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $builder
            ->add('deliveryAddress', SelectUserAddressFormType::class, [
                'label' => 'Où on t\'envoie ça ?',
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('paymentMethod', EntityType::class, [
                'label' => 'Comment tu veux payer ?',
                'class' => Payment::class,
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('message', TextType::class, [
                'label' => 'Ton message',
                'required' => false,
                'empty_data' => null,
                'attr' => ['maxlength' => 280, 'placeholder' => 'Ex. : je passe au concert du 22 à Bayonne'],
                'help' => 'Regrouper avec une autre commande, une dédicace, un retrait au concert… 280 caractères max.',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider ma commande',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderDeliveryDto::class,
        ]);
    }
}
