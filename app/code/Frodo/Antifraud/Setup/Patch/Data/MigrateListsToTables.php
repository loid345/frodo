<?php
/**
 * Copyright © Frodo. All rights reserved.
 */
declare(strict_types=1);

namespace Frodo\Antifraud\Setup\Patch\Data;

use DateTimeImmutable;
use DateTimeZone;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

class MigrateListsToTables implements DataPatchInterface
{
    private const XML_PATH_BLACKLIST_EMAILS = 'frodo_antifraud/general/blacklist_emails';
    private const XML_PATH_WHITELIST_EMAILS = 'frodo_antifraud/general/whitelist_emails';
    private const XML_PATH_LIMITED_EMAILS = 'frodo_antifraud/general/limited_emails';
    private const XML_PATH_BLACKLIST_IPS = 'frodo_antifraud/general/blacklist_ips';
    private const UTC_TIMEZONE = 'UTC';

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Initialize patch dependencies.
     *
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    /**
     * Migrate antifraud lists from core_config_data to dedicated tables.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');
        $now = (new DateTimeImmutable('now', new DateTimeZone(self::UTC_TIMEZONE)))->format('Y-m-d H:i:s');

        $this->migrateEmailList(
            $connection,
            $configTable,
            self::XML_PATH_BLACKLIST_EMAILS,
            'frodo_antifraud_blacklist_email',
            $now
        );

        $this->migrateEmailList(
            $connection,
            $configTable,
            self::XML_PATH_WHITELIST_EMAILS,
            'frodo_antifraud_whitelist_email',
            $now
        );

        $this->migrateLimitedEmails($connection, $configTable, $now);
        $this->migrateBlacklistIps($connection, $configTable, $now);

        $connection->delete($configTable, [
            'path IN (?)' => [
                self::XML_PATH_BLACKLIST_EMAILS,
                self::XML_PATH_WHITELIST_EMAILS,
                self::XML_PATH_LIMITED_EMAILS,
                self::XML_PATH_BLACKLIST_IPS,
            ],
        ]);

        $this->moduleDataSetup->endSetup();
    }

    /**
     * Migrate a plain email list config value to a dedicated table.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $configTable
     * @param string $configPath
     * @param string $targetTable
     * @param string $now
     * @return void
     */
    private function migrateEmailList($connection, string $configTable, string $configPath, string $targetTable, string $now): void
    {
        $rows = $connection->fetchAll(
            $connection->select()->from($configTable, ['value'])->where('path = ?', $configPath)
        );

        $targetTable = $this->resourceConnection->getTableName($targetTable);
        $logTable = $this->resourceConnection->getTableName('frodo_antifraud_action_log');

        foreach ($rows as $row) {
            $emails = $this->parseList((string)($row['value'] ?? ''));
            foreach ($emails as $email) {
                $email = strtolower(trim($email));
                if ($email === '') {
                    continue;
                }

                try {
                    $connection->insertOnDuplicate($targetTable, [
                        'email' => $email,
                        'reason' => 'Migrated from config',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['updated_at']);

                    $connection->insert($logTable, [
                        'action_type' => 'migration',
                        'target_type' => 'email',
                        'target_value' => $email,
                        'details' => sprintf('Migrated from %s', $configPath),
                        'created_at' => $now,
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->warning('Frodo Antifraud migration: ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Migrate limited emails with expiration timestamps.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $configTable
     * @param string $now
     * @return void
     */
    private function migrateLimitedEmails($connection, string $configTable, string $now): void
    {
        $rows = $connection->fetchAll(
            $connection->select()->from($configTable, ['value'])->where('path = ?', self::XML_PATH_LIMITED_EMAILS)
        );

        $targetTable = $this->resourceConnection->getTableName('frodo_antifraud_limited_email');
        $logTable = $this->resourceConnection->getTableName('frodo_antifraud_action_log');

        foreach ($rows as $row) {
            $entries = $this->parseList((string)($row['value'] ?? ''));
            foreach ($entries as $entry) {
                $parts = explode(':', $entry, 2);
                if (count($parts) !== 2) {
                    continue;
                }

                $email = strtolower(trim($parts[0]));
                $expiresAt = trim($parts[1]);
                if ($email === '' || $expiresAt === '') {
                    continue;
                }

                try {
                    $expiresAtDate = new DateTimeImmutable($expiresAt);
                    $expiresAtFormatted = $expiresAtDate->setTimezone(
                        new DateTimeZone(self::UTC_TIMEZONE)
                    )->format('Y-m-d H:i:s');

                    $connection->insertOnDuplicate($targetTable, [
                        'email' => $email,
                        'expires_at' => $expiresAtFormatted,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['expires_at', 'updated_at']);

                    $connection->insert($logTable, [
                        'action_type' => 'migration',
                        'target_type' => 'email',
                        'target_value' => $email,
                        'details' => sprintf('Limited email migrated, expires %s', $expiresAtFormatted),
                        'created_at' => $now,
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->warning('Frodo Antifraud migration (limited): ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Migrate IP blacklist entries.
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param string $configTable
     * @param string $now
     * @return void
     */
    private function migrateBlacklistIps($connection, string $configTable, string $now): void
    {
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($configTable, ['scope_id', 'value'])
                ->where('path = ?', self::XML_PATH_BLACKLIST_IPS)
        );

        $targetTable = $this->resourceConnection->getTableName('frodo_antifraud_blacklist_ip');
        $logTable = $this->resourceConnection->getTableName('frodo_antifraud_action_log');

        foreach ($rows as $row) {
            $storeId = (int)($row['scope_id'] ?? 0);
            $ips = $this->parseList((string)($row['value'] ?? ''));
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if ($ip === '') {
                    continue;
                }

                try {
                    $connection->insertOnDuplicate($targetTable, [
                        'ip_address' => $ip,
                        'store_id' => $storeId,
                        'reason' => 'Migrated from config',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], ['updated_at']);

                    $connection->insert($logTable, [
                        'action_type' => 'migration',
                        'target_type' => 'ip',
                        'target_value' => $ip,
                        'details' => sprintf('Migrated from config (store %d)', $storeId),
                        'created_at' => $now,
                    ]);
                } catch (\Exception $exception) {
                    $this->logger->warning('Frodo Antifraud migration (IP): ' . $exception->getMessage());
                }
            }
        }
    }

    /**
     * Parse a delimited config value into unique items.
     *
     * @param string $value
     * @return string[]
     */
    private function parseList(string $value): array
    {
        $items = preg_split('/[\s,;]+/', $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $items = array_map('trim', $items);
        $items = array_filter($items, static function (string $item): bool {
            return $item !== '';
        });

        return array_values(array_unique($items));
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
