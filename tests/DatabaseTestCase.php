<?php

namespace App\Tests;

use App\Tests\Seeder\UserSeeder;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ObjectRepository;
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

    protected static ?KernelBrowser $client;

    protected static ?UserSeeder $userSeeder;

    //protected static function bootKernel(array $options = []): KernelInterface
    //{
    //    $kernel = parent::bootKernel($options);
    //
    //    // For MySql testing database we have to reset database before each test.
    //    $platform = $kernel->getContainer()->get('doctrine')->getConnection()->getDatabasePlatform();
    //    if (! $platform instanceof SqlitePlatform) {
    //        // static::populateDatabase();
    //    }
    //
    //    return $kernel;
    //}

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
     * @param array $userData
     * @param bool|null $jwtAuth
     * @param string|null $authDeviceName
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser|null
     */
    protected function getAuthenticatedClient(array $userData, ?bool $jwtAuth = true, ?string $authDeviceName = null
    ): ?KernelBrowser {
        $client = static::getReusableClient();

        if ($jwtAuth) {
            $token = $userData['jwt_token'] ?? '';
        } else {
            /** @var \App\Module\User\Domain\User $user */
            $user = $userData['user'];
            $deviceTokens = $user->getAuthTokens()->toArray();
            if ($authDeviceName && $token = current(array_filter($deviceTokens,
                    function ($token) use ($authDeviceName) {
                        return $token->getName() === $authDeviceName;
                    }))) {
                /** @var \App\Module\User\Domain\AuthToken $token */
                $token = $token->getToken();
            } else {
                $token = $userData['app_token'] ?? '';
            }
        }

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        return $client;
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\KernelBrowser|null
     */
    protected function getAnonymousClient(): ?KernelBrowser
    {
        return static::getReusableClient();
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
