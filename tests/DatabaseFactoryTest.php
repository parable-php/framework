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
    }
}
