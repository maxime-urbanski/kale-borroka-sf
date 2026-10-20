<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * The step after the link of an e-mail change: the current password, so that a session alone
 * (left open, stolen) cannot move the account to another inbox.
 */
class ConfirmEmailChangeFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('current_password', PasswordType::class, [
            'label' => 'Mot de passe actuel',
            'attr' => ['autocomplete' => 'current-password'],
            'constraints' => [new UserPassword(message: 'Mot de passe actuel incorrect.')],
        ]);
    }
}
