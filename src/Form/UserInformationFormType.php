<?php

declare(strict_types=1);

namespace App\Form;

use App\Data\UpdateUserInformation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Informations (maquette 14), block « Identité ». The default address is chosen on the
 * address book.
 */
class UserInformationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name', 'maxlength' => 100],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name', 'maxlength' => 100],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'help' => 'Si tu le changes, on envoie un lien de confirmation à la nouvelle adresse.',
                'attr' => ['autocomplete' => 'email', 'maxlength' => 180],
            ])
            // Checked by the controller, only when the e-mail changes: moving the account to
            // another inbox is what a stolen session would do first.
            ->add('current_password', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'help' => 'Seulement pour changer d’e-mail.',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'current-password'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UpdateUserInformation::class,
        ]);
    }
}
