<?php

namespace App\Tests;

use App\Tests\Seeder\UserSeeder;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectRepository;
use Hautelook\AliceBundle\PhpUnit\BaseDatabaseTrait;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTManager;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class DatabaseTestCase extends WebTestCase
{
    use InteractsWithMessenger;

    // Only when using MySql as testing database.
    use BaseDatabaseTrait;

    protected static ?KernelBrowser $client;

    protected static ?UserSeeder $userSeeder;

    protected static function bootKernel(array $options = []): KernelInterface
    {
        static::ensureKernelTestCase();
        $kernel = parent::bootKernel($options);

        // For MySql testing database we have to reset database before each test.
        $platform = $kernel->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform();
        if (! $platform instanceof SqlitePlatform) {
            static::populateDatabase();
        }

        return $kernel;
    }

    protected function setUp(): void
    {
        parent::setUp();

        static::$client = static::createClient();

        // We are sure that kernel is booted at this point, see createClient().
        if ('test' !== self::$kernel->getEnvironment()) {
            throw new LogicException('Execution only in Test environment possible!');
        }

        $connection = $this->getContainer()->get('doctrine')->getConnection();
        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof SqlitePlatform) {
            // When testing against Sqlite database we must do special init.
            $this->initDatabase();
            $connection->executeStatement("PRAGMA foreign_keys = ON;");
        }

        $container = static::getContainer();
        $passwordHasher = $container->get(UserPasswordHasher::class);
        $JWTManager = $container->get(JWTManager::class);

        static::$userSeeder = new UserSeeder($this->getEntityManager(), $passwordHasher, $JWTManager);
    }

    protected function tearDown(): void
    {
        $this->getEntityManager()->close();

        static::$client = null;

        static::$userSeeder = null;

        parent::tearDown();
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser|null
     */
    protected static function getReusableClient(): ?KernelBrowser
    {
        if (isset(static::$client)) {
            return static::$client;
        }

        static::$client = static::createClient();

        return static::$client;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        $client = static::getReusableClient();

        return $client->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param string $class - entity class.
     * @return \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    protected function getRepository(string $class): EntityRepository|ObjectRepository
    {
        return $this->getEntityManager()->getRepository($class);
    }

    /**
     * @param string $class - repository class.
     * @return \Doctrine\ORM\EntityRepository|\Doctrine\Persistence\ObjectRepository
     */
    protected function getNativeRepository(string $class): EntityRepository|ObjectRepository
    {
        return static::getContainer()->get($class);
    }

    /**
     * Init in-memory database.
     *
     * @return void
     */
    private function initDatabase(): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->getEntityManager();
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);
    }
}
