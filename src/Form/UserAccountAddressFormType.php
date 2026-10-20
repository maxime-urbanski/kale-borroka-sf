<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An address of the address book (maquette 13), in the order the form shows it. Whether it is
 * the default one is not mapped: the controller hands the default over (UserDefaultAddressInterface),
 * so that there is always exactly one.
 */
class UserAccountAddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                // Never null: the setters take a string, NotBlank tells what is missing.
                'empty_data' => '',
                'label' => 'Nom de l’adresse',
                'help' => 'Ex. Domicile, Le local, Chez Ama',
                'attr' => ['autocomplete' => 'off', 'maxlength' => 100],
            ])
            ->add('country', TextType::class, [
                'empty_data' => '',
                'label' => 'Pays',
                'attr' => ['autocomplete' => 'country-name', 'maxlength' => 100],
            ])
            ->add('firstname', TextType::class, [
                'empty_data' => '',
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name', 'maxlength' => 100],
            ])
            ->add('lastname', TextType::class, [
                'empty_data' => '',
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name', 'maxlength' => 100],
            ])
            ->add('address', TextType::class, [
                'empty_data' => '',
                'label' => 'Adresse',
                'attr' => ['autocomplete' => 'address-line1', 'maxlength' => 255],
            ])
            ->add('complement_address', TextType::class, [
                'label' => 'Complément',
                'help' => 'Étage, digicode, c/o…',
                'required' => false,
                'empty_data' => '',
                'attr' => ['autocomplete' => 'address-line2', 'maxlength' => 255],
            ])
            ->add('zipcode', TextType::class, [
                'empty_data' => '',
                'label' => 'Code postal',
                'attr' => ['autocomplete' => 'postal-code', 'maxlength' => 15],
            ])
            ->add('city', TextType::class, [
                'empty_data' => '',
                'label' => 'Ville',
                'attr' => ['autocomplete' => 'address-level2', 'maxlength' => 100],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'help' => 'Uniquement pour le transporteur.',
                'required' => false,
                'attr' => ['autocomplete' => 'tel', 'maxlength' => 30],
            ])
            ->add('makeDefault', CheckboxType::class, [
                'label' => 'Adresse de livraison par défaut',
                'mapped' => false,
                'required' => false,
                // The default address stays so until another one takes over.
                'data' => $options['is_default'],
                'disabled' => $options['is_default'],
                'help' => $options['is_default'] ? 'Pour en changer, choisis une autre adresse par défaut.' : null,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Address::class,
            'is_default' => false,
        ]);
        $resolver->setAllowedTypes('is_default', 'bool');
    }
}
