<?php

namespace App\Service;

use App\Entity\Entreprise;
use Doctrine\ORM\EntityManagerInterface;

class SlugGenerator
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function generate(string $name, ?int $excludeId = null): string
    {
        $base = $this->slugify($name);

        if (empty($base)) {
            $base = 'entreprise';
        }

        $candidate = $base;
        $counter = 2;

        while ($this->slugExists($candidate, $excludeId)) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function slugify(string $name): string
    {
        // Transliterate accents to ASCII
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        if ($slug === false) {
            $slug = $name;
        }

        // Lowercase
        $slug = strtolower($slug);

        // Replace non-alphanumeric with hyphens
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Trim leading/trailing hyphens
        $slug = trim($slug, '-');

        return $slug ?? '';
    }

    private function slugExists(string $slug, ?int $excludeId): bool
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Entreprise::class, 'e')
            ->where('e.slug = :slug')
            ->setParameter('slug', $slug);

        if ($excludeId !== null) {
            $qb->andWhere('e.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
