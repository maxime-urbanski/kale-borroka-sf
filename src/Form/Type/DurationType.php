<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A duration stored in seconds, typed as "3:45" (or "1:02:30", or plain seconds).
 */
class DurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?int $seconds): string => null === $seconds ? '' : self::format($seconds),
            static function (?string $input): ?int {
                $input = trim((string) $input);

                if ('' === $input) {
                    return null;
                }

                if (!preg_match('/^(?:(\d+):)?(?:(\d+):)?(\d+)$/', $input, $parts)) {
                    $failure = new TransformationFailedException('Invalid duration.');
                    $failure->setInvalidMessage('Durée invalide : utilisez le format 3:45.');

                    throw $failure;
                }

                $numbers = array_map('intval', array_values(array_filter(\array_slice($parts, 1), static fn (string $part): bool => '' !== $part)));
                $seconds = 0;
                foreach ($numbers as $number) {
                    $seconds = $seconds * 60 + $number;
                }

                return $seconds;
            },
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'attr' => ['placeholder' => '3:45', 'inputmode' => 'numeric'],
            'invalid_message' => 'Durée invalide : utilisez le format 3:45.',
        ]);
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public static function format(int $seconds): string
    {
        return $seconds >= 3600
            ? \sprintf('%d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60)
            : \sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
