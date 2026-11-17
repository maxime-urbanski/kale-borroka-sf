<?php

declare(strict_types=1);

namespace App\Form;

use App\Data\OrderShippingDto;
use App\Entity\Transporter;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderShippingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('transporter', EntityType::class, [
                'label' => 'Transporteur',
                'class' => Transporter::class,
                'placeholder' => 'Choisir…',
                // The list price is a reminder: the amount below is what the buyer pays.
                'choice_label' => static fn (Transporter $transporter): string => \sprintf('%s (tarif %s €)', $transporter->getName(), number_format((int) $transporter->getPrice() / 100, 2, ',', ' ')),
            ])
            ->add('shippingPrice', MoneyType::class, [
                'label' => 'Port',
                'currency' => 'EUR',
                'divisor' => 100,
                'input' => 'integer',
                'help' => '0 si le port est offert.',
            ])
            ->add('submit', SubmitType::class, ['label' => 'Enregistrer le port']);
    }

    public function getBlockPrefix(): string
    {
        return 'order_shipping';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => OrderShippingDto::class]);
    }
}
