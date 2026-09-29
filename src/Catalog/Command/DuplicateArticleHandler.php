<?php

declare(strict_types=1);

namespace App\Catalog\Command;

use App\Entity\Article;
use App\Entity\MerchVariant;
use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus')]
readonly class DuplicateArticleHandler
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * The copy gets its id when the bus transaction is flushed, i.e. once dispatch() returns.
     */
    public function __invoke(DuplicateArticle $command): Article
    {
        $original = $this->articleRepository->find($command->articleId)
            ?? throw new \InvalidArgumentException(\sprintf('Article #%d does not exist.', $command->articleId));

        $copy = clone $original;

        // A variant's name is derived from its design, size and colour.
        if (!$copy instanceof MerchVariant) {
            $copy->setName($original->getName().' (copie)');
        }

        $this->entityManager->persist($copy);

        return $copy;
    }
}
