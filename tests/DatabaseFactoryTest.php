<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Config;
use Parable\Framework\DatabaseFactory;
use Parable\Orm\Database;

class DatabaseFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testDatabaseFromMysqlData(): void
    {
        $config = new Config();
        $config->set('parable.database', [
            'type' => Database::TYPE_MYSQL,
            'host' => 'localhost',
            'username' => 'test_user',
            'password' => 'test_pass',
            'database' => 'test_database',
        ]);

        $factory = new DatabaseFactory();

        $database = $factory->createFromConfig($config);

        self::assertInstanceOf(Database::class, $database);
        self::assertSame(Database::TYPE_MYSQL, $database->getType());
        self::assertSame('localhost', $database->getHost());
        self::assertSame('test_user', $database->getUsername());
        self::assertSame('test_pass', $database->getPassword());
        self::assertSame('test_database', $database->getDatabaseName());
    }

    public function testDatabaseFromSqliteData(): void
    {
        $config = new Config();
        $config->set('parable.database', [
            'type' => Database::TYPE_SQLITE,
            'database' => 'test_database.sqlite',
        ]);

        $factory = new DatabaseFactory();

        $database = $factory->createFromConfig($config);

        self::assertInstanceOf(Database::class, $database);
        self::assertSame(Database::TYPE_SQLITE, $database->getType());
        self::assertSame('test_database.sqlite', $database->getDatabaseName());
    }
}
