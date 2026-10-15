<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Creează contul administratorului',
)]
class CreateAdminCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $email = strtolower(trim((string) $io->ask('E-mail administrator')));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('Adresa de e-mail nu este validă.');

            return Command::FAILURE;
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            $io->error('Există deja un utilizator cu acest e-mail.');

            return Command::FAILURE;
        }

        $password = (string) $io->askHidden('Parola (minimum 12 caractere)');

        if (mb_strlen($password) < 12) {
            $io->error('Parola trebuie să aibă minimum 12 caractere.');

            return Command::FAILURE;
        }

        $confirmation = (string) $io->askHidden('Confirmă parola');

        if ($password !== $confirmation) {
            $io->error('Parolele nu coincid.');

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles(['ROLE_ADMIN']);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password,
        );

        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success('Contul administratorului a fost creat.');

        return Command::SUCCESS;
    }
}