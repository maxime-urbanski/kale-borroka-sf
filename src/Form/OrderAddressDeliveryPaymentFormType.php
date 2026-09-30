<?php

namespace App\Form;

use App\Data\OrderDeliveryDto;
use App\Entity\Payment;
use App\Entity\Transporter;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
                'label' => false,
            ])
            ->add('transporter', EntityType::class, [
                'label' => 'Choix du transporteur',
                'class' => Transporter::class,
                // The price is added to the order total (free from the shop's threshold).
                'choice_label' => static fn (Transporter $transporter): string => \sprintf('%s — %s €', $transporter->getName(), number_format((int) $transporter->getPrice() / 100, 2, ',', ' ')),
                'multiple' => false,
                'expanded' => true,
                'required' => true,
            ])
            ->add('paymentMethod', EntityType::class, [
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
            'data_class' => OrderDeliveryDto::class,
        ]);
    }
}
