<?php

namespace App\Command;

use App\Entity\User;
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
    public function __construct(
        private readonly UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager
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
        $email = $input->getArgument('email');

        if (!$email) {
            $emailValue = $io->ask('Merci de renseigner l\'adresse email.', 'admin@kbr.com');
            $input->setArgument('email', $emailValue);
        }

        $finalEmail = $input->getArgument('email');

        $existingUser = $this->userRepository->findOneBy(['email' => $finalEmail]);

        if (!$existingUser) {
            $newUser = new User();
            $password = $this->getValidPassword($io);
            $newUser->setEmail($finalEmail);
            $newUser->setPassword(
                $this->passwordHasher->hashPassword($newUser, $password)
            );
            $newUser->setRoles(['ROLE_ADMIN']);

            $this->entityManager->persist($newUser);

            $io->success('Administrateur créé. Vous pouvez utiliser l\'email et le mot de passe pour vous connecter.');
        } else {
            $existingUserRole = $existingUser->getRoles();

            if (!in_array('ROLE_ADMIN', $existingUserRole)) {
                $addAdminRole = $io->ask('Passer l\'utilisateur '.$existingUser->getEmail().' en tant qu\'administrateur ?', 'yes');
                if ('no' === $addAdminRole) {
                    $io->warning('Rôle inchangé !');

                    return Command::FAILURE;
                }
                $existingUser->setRoles(['ROLE_ADMIN']);
                $this->entityManager->persist($existingUser);
                $io->success('L\'utilisateur '.$existingUser->getEmail().' est maintenant un administrateur.');
            } else {
                $io->success('L\'utilisateur '.$existingUser->getEmail().' est déjà un administrateur.');
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }

    private function getValidPassword(SymfonyStyle $io): string
    {
        for ($attempt = 1; $attempt <= 2; ++$attempt) {
            $password = $io->ask('Créer un mot de passe.');
            $confirmPassword = $io->ask('Confirmer le mot de passe.');

            if ($password === $confirmPassword && !empty($password)) {
                return $password;
            }
            $io->error('Les mots de passe sont différents ou vides. Réessai '.$attempt.'/'. 2);
        }

        $io->error('Trop de tentatives infructueuses. Abandon.');

        return Command::FAILURE;
    }
}
