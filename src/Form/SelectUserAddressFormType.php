<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SelectUserAddressFormType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    /**
     * Options are resolved once per form type and cached by the form registry, which lives
     * as long as the FrankenPHP worker. Nothing about the current user may be computed here:
     * the query builder closure reads it when the choice list is built, at each request.
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Address::class,
            'required' => true,
            'choice_label' => static fn (Address $address): string => $address->getAddress().' '.$address->getComplementAddress().' '.$address->getZipcode().$address->getCity().' '.$address->getCountry(),
            'query_builder' => fn (AddressRepository $addressRepository): QueryBuilder => $addressRepository
                ->createQueryBuilder('address')
                ->where('address.users = :user')
                // No user: an empty list rather than every address.
                ->setParameter('user', $this->security->getUser() instanceof User ? $this->security->getUser() : null),
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
