<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Entity\UserCollection;
use App\Entity\Wishlist;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user',
)]
class CreateAdminCommand extends Command
{
    private const int MIN_PASSWORD_LENGTH = 12;
    private const int PASSWORD_ATTEMPTS = 2;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'email associer au nouvel administrateur.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = (string) ($input->getArgument('email') ?: $io->ask('Merci de renseigner l\'adresse email.', 'admin@kbr.com'));

        $existingUser = $this->userRepository->findOneByEmail($email);

        if (null === $existingUser) {
            return $this->createAdmin($io, $email);
        }

        if (\in_array('ROLE_ADMIN', $existingUser->getRoles(), true)) {
            $io->success('L\'utilisateur '.$existingUser->getEmail().' est déjà un administrateur.');

            return Command::SUCCESS;
        }

        if (!$io->confirm('Passer l\'utilisateur '.$existingUser->getEmail().' en tant qu\'administrateur ?')) {
            $io->warning('Rôle inchangé !');

            return Command::FAILURE;
        }

        $existingUser->setRoles(['ROLE_ADMIN']);
        $this->entityManager->flush();
        $io->success('L\'utilisateur '.$existingUser->getEmail().' est maintenant un administrateur.');

        return Command::SUCCESS;
    }

    private function createAdmin(SymfonyStyle $io, string $email): int
    {
        $password = $this->askPassword($io);

        if (null === $password) {
            $io->error('Trop de tentatives infructueuses. Abandon : aucun compte créé.');

            return Command::FAILURE;
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstname((string) $io->ask('Prénom', 'Admin'))
            ->setLastname((string) $io->ask('Nom', 'Kale Borroka'))
            ->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        // Same as RegistrationController: every account has a wishlist and a collection.
        $this->entityManager->persist($user);
        $this->entityManager->persist((new Wishlist())->setUser($user));
        $this->entityManager->persist((new UserCollection())->setUser($user));
        $this->entityManager->flush();

        $io->success('Administrateur créé. Vous pouvez utiliser l\'email et le mot de passe pour vous connecter.');

        return Command::SUCCESS;
    }

    /**
     * Null when every attempt failed: the caller must stop, never fall back to a default.
     */
    private function askPassword(SymfonyStyle $io): ?string
    {
        for ($attempt = 1; $attempt <= self::PASSWORD_ATTEMPTS; ++$attempt) {
            $password = (string) $io->askHidden('Créer un mot de passe ('.self::MIN_PASSWORD_LENGTH.' caractères minimum).');
            $confirmPassword = (string) $io->askHidden('Confirmer le mot de passe.');

            if ($password === $confirmPassword && mb_strlen($password) >= self::MIN_PASSWORD_LENGTH) {
                return $password;
            }

            $io->error(\sprintf('Les mots de passe sont différents ou trop courts. Essai %d/%d.', $attempt, self::PASSWORD_ATTEMPTS));
        }

        return null;
    }
}
