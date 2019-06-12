<?php declare(strict_types=1);

namespace Parable\Framework\Tests;

use Parable\Framework\Config;
use Parable\Framework\DatabaseFactory;
use Parable\Framework\Exception;
use Parable\Orm\Database;
use PDO;

class DatabaseFactoryTest extends AbstractTestCase
{
    public function testDatabaseFromMysqlData(): void
    {
        $config = new Config();
        $config->set('parable.database', [
            'type' => Database::TYPE_MYSQL,
            'host' => 'localhost',
            'port' => 1337,
            'username' => 'test_user',
            'password' => 'test_pass',
            'database' => 'test_database',
            'charSet' => 'utf8',
            'errorMode' => PDO::ERRMODE_EXCEPTION,
        ]);

        $database = (new DatabaseFactory())->createFromConfig($config);

        self::assertInstanceOf(Database::class, $database);
        self::assertSame(Database::TYPE_MYSQL, $database->getType());
        self::assertSame('localhost', $database->getHost());
        self::assertSame(1337, $database->getPort());
        self::assertSame('test_user', $database->getUsername());
        self::assertSame('test_pass', $database->getPassword());
        self::assertSame('test_database', $database->getDatabaseName());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getErrorMode());
    }

    public function testDatabaseFromSqliteData(): void
    {
        $config = new Config();
        $config->set('parable.database', [
            'type' => Database::TYPE_SQLITE,
            'database' => 'test_database.sqlite',
            'errorMode' => PDO::ERRMODE_EXCEPTION,
        ]);

        $database = (new DatabaseFactory())->createFromConfig($config);

        self::assertInstanceOf(Database::class, $database);
        self::assertSame(Database::TYPE_SQLITE, $database->getType());
        self::assertSame('test_database.sqlite', $database->getDatabaseName());
        self::assertSame(PDO::ERRMODE_EXCEPTION, $database->getErrorMode());
    }

    public function testThrowsExceptionIfTypeIsUnknown(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unknown database type: yoloDb.");

        $config = new Config();
        $config->set('parable.database', [
            'type' => 'yoloDb',
        ]);

        (new DatabaseFactory())->createFromConfig($config);
    }

    public function testThrowsExceptionIfNoDatabaseConfigAvailable(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Cannot create database from provided config.");

        (new DatabaseFactory())->createFromConfig(new Config());
    }
}
