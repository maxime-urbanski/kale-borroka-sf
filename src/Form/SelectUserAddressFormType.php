<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $resolver->setDefaults([
            'class' => Address::class,
            'required' => true,
            'choices' => $user->getAddresses(),
            'choice_label' => function (Address $address) {
                return $address->getAddress().' '.$address->getComplementAddress().' '.$address->getZipcode().$address->getCity().' '.$address->getCountry();
            },
            'query_builder' => function (AddressRepository $addressRepository) use ($user) {
                return $addressRepository
                    ->createQueryBuilder('address')
                    ->leftJoin('address.users', 'users')
                    ->where('users = :user')
                    ->setParameter('user', $user);
            },
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
