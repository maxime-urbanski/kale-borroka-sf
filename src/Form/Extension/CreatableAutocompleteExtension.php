<?php

declare(strict_types=1);

namespace App\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\CrudAutocompleteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Lets an EasyAdmin autocomplete create the entity the user typed when it does not exist
 * yet ("create on the fly"): set the `create_missing` option to a factory,
 * `fn (string $name): Artist => (new Artist())->setName($name)`.
 *
 * assets/admin.js makes TomSelect submit new entries as "__new__:<name>". They are turned
 * into ids before EasyAdmin's own PRE_SUBMIT listener validates them: an entity with that
 * exact name is reused, otherwise the factory's is persisted and flushed right away (so
 * it may outlive a form that then fails validation — it is simply reused on the next try).
 */
class CreatableAutocompleteExtension extends AbstractTypeExtension
{
    public const string NEW_VALUE_PREFIX = '__new__:';

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    /**
     * EntityType too: EasyAdmin forwards every option of the autocomplete to the inner
     * EntityType, which must therefore accept `create_missing` (and ignore it).
     */
    public static function getExtendedTypes(): iterable
    {
        return [CrudAutocompleteType::class, EntityType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('create_missing', null)
            ->setAllowedTypes('create_missing', ['null', 'callable'])
            // EasyAdmin forwards `attr` to the inner <select> TomSelect is built on.
            ->addNormalizer('attr', static fn (Options $options, array $attr): array => null === $options['create_missing']
                ? $attr
                : $attr + ['data-kbr-autocomplete-create' => 'true']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (null === $options['create_missing'] || !$builder->getType()->getInnerType() instanceof CrudAutocompleteType) {
            return;
        }

        /** @var class-string $class */
        $class = $options['class'];
        $factory = $options['create_missing'];

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($class, $factory): void {
            $data = $event->getData();

            if (!\is_array($data) || !isset($data['autocomplete'])) {
                return;
            }

            $multiple = \is_array($data['autocomplete']);
            $values = [];

            foreach ((array) $data['autocomplete'] as $value) {
                if (\is_string($value) && str_starts_with($value, self::NEW_VALUE_PREFIX)) {
                    $value = $this->idOf($class, trim(substr($value, \strlen(self::NEW_VALUE_PREFIX))), $factory);
                }

                if (null !== $value && '' !== $value) {
                    $values[] = $value;
                }
            }

            $data['autocomplete'] = $multiple ? $values : ($values[0] ?? '');
            $event->setData($data);
        }, 10);
    }

    /**
     * @param class-string $class
     */
    private function idOf(string $class, string $name, callable $factory): ?string
    {
        if ('' === $name) {
            return null;
        }

        $manager = $this->managerRegistry->getManagerForClass($class)
            ?? throw new \LogicException(\sprintf('%s is not a Doctrine entity.', $class));
        $entity = $manager->getRepository($class)->findOneBy(['name' => $name]);

        if (null === $entity) {
            $entity = $factory($name);
            $manager->persist($entity);
            $manager->flush();
        }

        $ids = $manager->getClassMetadata($class)->getIdentifierValues($entity);

        return (string) reset($ids);
    }
}
