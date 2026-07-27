<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يصدّر بيانات التطبيق من اتصال Laravel الحالي (أو من ملف SQLite محدد) إلى ملف INSERT لـ MySQL
 * (للاستيراد على MySQL بعد وجود الجداول من المايغريشن — الملف يحتوي INSERT فقط بدون سكيما).
 */
class ExportLocalDataToMysqlFileCommand extends Command
{
    protected $signature = 'export:mysql-data
                            {--output= : مسار ملف .sql (افتراضي: سطح المكتب)}
                            {--sqlite= : مسار ملف database.sqlite قديم (نفس مخطط Laravel) للتصدير منه بدل القاعدة الحالية}';

    protected $description = 'تصدير INSERT فقط (بدون سكيما) لـ MySQL — يدعم --sqlite لنسخة قديمة';

    private const EXPORT_SQLITE_CONNECTION = 'export_sqlite_source';

    /** @return list<string> */
    private function tablesInDependencyOrder(): array
    {
        return [
            'users',
            'password_reset_tokens',
            'sessions',
            'clients',
            'suppliers',
            'client_contacts',
            'supplier_contacts',
            'products',
            'product_currency_prices',
            'invoices',
            'invoice_lines',
            'client_payments',
            'client_balance_adjustments',
            'purchase_orders',
            'purchase_order_lines',
            'supplier_payments',
            'supplier_balance_adjustments',
            'expenses',
            'income_entries',
            'legacy_catalog_products',
            'legacy_catalog_projects',
            'import_audit',
        ];
    }

    public function handle(): int
    {
        $sqlitePath = $this->option('sqlite');
        if ($sqlitePath) {
            $resolved = realpath($sqlitePath);
            if ($resolved === false || ! is_file($resolved)) {
                $this->error('ملف SQLite غير موجود: '.$sqlitePath);

                return self::FAILURE;
            }
            config([
                'database.connections.'.self::EXPORT_SQLITE_CONNECTION => [
                    'driver' => 'sqlite',
                    'database' => $resolved,
                    'prefix' => '',
                    'foreign_key_constraints' => false,
                ],
            ]);
            DB::purge(self::EXPORT_SQLITE_CONNECTION);
            $connectionName = self::EXPORT_SQLITE_CONNECTION;
            $this->info('المصدر: ملف SQLite → '.$resolved);
        } else {
            $connectionName = (string) config('database.default');
            $this->info('المصدر: اتصال Laravel الافتراضي → '.$connectionName);
        }

        $connection = DB::connection($connectionName);
        $pdo = $connection->getPdo();
        $driver = $connection->getDriverName();

        $defaultPath = $this->defaultOutputPath();
        $path = $this->option('output') ?: $defaultPath;

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info('محرك القاعدة: '.$driver);
        $this->info('ملف الإخراج: '.$path);

        $lines = [];
        $lines[] = '-- غاز اليمين — بيانات فقط (INSERT) — بدون CREATE / DROP / ALTER';
        $lines[] = '-- للاستيراد على MySQL/MariaDB بعد أن تكون الجداول موجودة (php artisan migrate --force)';
        $lines[] = '-- افرغ الصفوف يدوياً أو بـ TRUNCATE إن أردت تجنب duplicate key قبل الاستيراد';
        if ($sqlitePath) {
            $lines[] = '-- مصدر البيانات: ملف SQLite خارجي';
        }
        $lines[] = '';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = '';

        foreach ($this->tablesInDependencyOrder() as $table) {
            if (! Schema::connection($connectionName)->hasTable($table)) {
                $this->warn('تخطّي (غير موجود): '.$table);

                continue;
            }

            $rows = $connection->table($table)->get();
            if ($rows->isEmpty()) {
                $this->line('فارغ: '.$table);

                continue;
            }

            $columns = array_keys((array) $rows->first());
            $colList = '`'.implode('`,`', $columns).'`';

            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $this->quoteMysqlValue($pdo, $row->{$col} ?? null);
                }
                $lines[] = 'INSERT INTO `'.$table.'` ('.$colList.') VALUES ('.implode(',', $values).');';
            }
            $lines[] = '';
            $this->info('صُدّر: '.$table.' ('.$rows->count().' صف)');
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';

        file_put_contents($path, implode("\n", $lines));

        $readmePath = preg_replace('/\.sql$/', '_تعليمات_الاستيراد.txt', $path) ?? ($path.'_تعليمات.txt');
        file_put_contents($readmePath, $this->readmeArabic());

        $this->info('تم. ملف SQL + ملف التعليمات بجانبه.');

        return self::SUCCESS;
    }

    private function defaultOutputPath(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
        $desktop = $home !== '' ? $home.DIRECTORY_SEPARATOR.'Desktop' : storage_path('app/exports');

        return $desktop.DIRECTORY_SEPARATOR.'profile_media_mysql_export_'.date('Y-m-d_His').'.sql';
    }

    private function readmeArabic(): string
    {
        return <<<'TXT'
غاز اليمين — استيراد بيانات فقط (INSERT) على السيرفر
====================================================

الملف لا يحتوي على CREATE TABLE — الجداول يجب أن تكون موجودة مسبقاً (migrate).

1) على السيرفر:
   cd /home/sarfesak/public_html/gaz
   php artisan migrate --force

2) افرغ صفوف الجداول إن لزم (أو قاعدة جديدة) لتجنب duplicate entry.

3) استيراد الملف:
   mysql -u sarfesak_gaz -p sarfesak_gaz < profile_media_DATA_ONLY_inserts.sql

   أو من phpMyAdmin: استيراد → اختر الملف.

4) لتصدير من نسخة قديمة من database.sqlite:
   php artisan export:mysql-data --sqlite=/المسار/database.sqlite --output=~/Desktop/اسم.sql

5) لا ترفع ملف .sql إلى Git إن احتوى بيانات حساسة.
TXT;
    }

    private function quoteMysqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value === true || $value === false) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return $this->formatFloat($value);
        }

        if (is_string($value) && is_numeric($value) && preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $pdo->quote($value->format('Y-m-d H:i:s'));
        }

        if (is_string($value)) {
            return $pdo->quote($value);
        }

        if (is_array($value) || is_object($value)) {
            return $pdo->quote(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }

        return $pdo->quote((string) $value);
    }

    private function formatFloat(float $value): string
    {
        if (is_nan($value) || is_infinite($value)) {
            return 'NULL';
        }

        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.') ?: '0';
    }
}
