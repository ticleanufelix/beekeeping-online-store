<?php

namespace App\Command;

use App\Entity\Article;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-demo-article',
    description: 'Creează un articol de probă în baza de date',
)]
class CreateDemoArticleCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $article = new Article();

        $article->setTitle('Articol publicat de probă');
       $article->setSlug('articol-publicat-de-proba');
        $article->setSummary('Un articol pentru a înțelege salvarea prin Doctrine.');
        $article->setContent('Acest articol a fost creat ca obiect PHP și salvat în MariaDB.');
        $article->setCategory('Sfaturi');
        $article->setPublished(true);
        $article->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        $io->success('Articol creat cu ID-ul: ' . $article->getId());

        return Command::SUCCESS;
    }
}