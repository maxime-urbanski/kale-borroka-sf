<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\User;
use App\Validator\PasswordRequirements;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Short on purpose: the address comes later, in the order tunnel.
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['autocomplete' => 'given-name', 'maxlength' => 100],
                'constraints' => [
                    new NotBlank(message: 'Indique ton prénom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
                'attr' => ['autocomplete' => 'family-name', 'maxlength' => 100],
                'constraints' => [
                    new NotBlank(message: 'Indique ton nom.'),
                    new Length(max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'attr' => ['autocomplete' => 'email', 'maxlength' => 180],
                'constraints' => [
                    new NotBlank(message: 'Indique ton adresse e-mail.'),
                    new Email(message: 'Adresse e-mail incomplète.'),
                    new Length(max: 180),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password', 'minlength' => PasswordRequirements::MIN_LENGTH],
                'constraints' => [new PasswordRequirements()],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Accepte les conditions pour créer ton compte.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
