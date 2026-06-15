<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration Doctrine avec rollback transactionnel.
 */
abstract class AbstractDoctrineTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $connection = $this->entityManager->getConnection();

        try {
            $connection->connect();
        } catch (\Throwable $e) {
            static::markTestSkipped('Base de test inaccessible : '.$e->getMessage());
        }

        if (!$connection->isTransactionActive()) {
            $connection->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        parent::tearDown();
    }
}
