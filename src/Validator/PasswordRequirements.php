<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraints\Compound;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Rules for every new password (registration, reset, account): the ones the
 * forms list under the field (templates/security/_password_field.html.twig).
 */
#[\Attribute]
final class PasswordRequirements extends Compound
{
    public const int MIN_LENGTH = 12;

    /**
     * A digit or anything that is neither a letter (with its accents, even decomposed) nor a
     * space (Unicode ones included): the same in PHP's PCRE and in the JS checklist.
     */
    public const string DIGIT_OR_SYMBOL = '[^\p{L}\p{M}\p{Z}\s]';

    /**
     * When a confirmation differs: the RepeatedType's invalid_message, and what the JS says as
     * one types (passed to it by _password_field.html.twig), so the two never drift apart.
     */
    public const string MISMATCH = 'Les deux mots de passe diffèrent.';

    protected function getConstraints(array $options): array
    {
        return [
            new NotBlank(message: 'Choisis un mot de passe.'),
            new Length(
                min: self::MIN_LENGTH,
                // max length allowed by Symfony for security reasons
                max: 4096,
                minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.',
            ),
            new Regex(
                pattern: '/'.self::DIGIT_OR_SYMBOL.'/u',
                message: 'Le mot de passe doit contenir un chiffre ou un symbole.',
            ),
            // Skipped when api.pwnedpasswords.com cannot be reached: registering or resetting
            // a password must not fail on someone else's outage.
            new NotCompromisedPassword(
                message: 'Ce mot de passe a fuité lors d\'un piratage : choisis-en un autre.',
                skipOnError: true,
            ),
        ];
    }
}
