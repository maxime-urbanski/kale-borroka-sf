<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\AddressRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserInformationFormType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastname', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('firstname', TextType::class, [
                'label' => 'Prenom',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adress email',
            ])
            ->add('defaultAddress', EntityType::class, [
                'label' => 'Mon adresse principal de livraison',
                'class' => Address::class,
                'choice_label' => function (Address $address) {
                    return $address->getName().' '.$address->getAddress().' '.$address->getComplementAddress().' '.$address->getZipcode().$address->getCity().' '.$address->getCountry();
                },
                'query_builder' => function (AddressRepository $addressRepository) {
                    $user = $this->security->getUser();

                    return $addressRepository
                        ->createQueryBuilder('address')
                        ->leftJoin('address.users', 'users')
                        ->where('users = :user')
                        ->setParameter('user', $user);
                },
                'multiple' => false,
                'required' => true,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider mes modifications',
                'attr' => [
                    'class' => 'btn-success',
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
