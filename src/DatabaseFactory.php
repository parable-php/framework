<?php declare(strict_types=1);

namespace Parable\Framework;

use Parable\Orm\Database;

class DatabaseFactory
{
    public function createFromConfig(Config $config): ?Database
    {
        $configValues = [
            'host' => null,
            'database' => null,
            'username' => null,
            'password' => null,
            'port' => null,
            'errorMode' => null,
            'charSet' => null,
        ];

        $configValuesMerged = array_merge(
            $configValues,
            $config->get('parable.database') ?? []
        );

        if ($config->get('parable.database.type') === 0) {
            return $this->createMySqlDatabaseFromConfigValues($configValuesMerged);
        } elseif ($config->get('parable.database.type') === 1) {
            return $this->createSqliteDatabaseFromConfigValues($configValuesMerged);
        }

        throw new Exception('Could not create Database from config.');
    }

    protected function createMySqlDatabaseFromConfigValues(array $configValues): Database
    {
        $database = new Database();
        $database->setType(Database::TYPE_MYSQL);

        if ($configValues['host'] !== null) {
            $database->setHost($configValues['host']);
        }

        if ($configValues['database'] !== null) {
            $database->setDatabaseName($configValues['database']);
        }

        if ($configValues['username'] !== null) {
            $database->setUsername($configValues['username']);
        }

        if ($configValues['password'] !== null) {
            $database->setPassword($configValues['password']);
        }

        if ($configValues['port'] !== null) {
            $database->setPort($configValues['port']);
        }

        if ($configValues['errorMode'] !== null) {
            $database->setErrorMode($configValues['errorMode']);
        }

        if ($configValues['charSet'] !== null) {
            $database->setCharSet($configValues['charSet']);
        }

        return $database;
    }

    protected function createSqliteDatabaseFromConfigValues(array $configValues): Database
    {
        $database = new Database();
        $database->setType(Database::TYPE_SQLITE);

        if ($configValues['database'] !== null) {
            $database->setDatabaseName($configValues['database']);
        }

        if ($configValues['errorMode'] !== null) {
            $database->setErrorMode($configValues['errorMode']);
        }

        return $database;
    }
}
