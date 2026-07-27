<?php

namespace App\Console\Commands;

use App\Services\LegacyErpImport\LegacyErpImportService;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyErpCommand extends Command
{
    protected $signature = 'legacy-erp:import
                            {--dry-run : احتساب الصفوف في قاعدة ERP دون إدراج}
                            {--connection=legacy_erp : اسم اتصال قاعدة ERP في config/database.php}';

    protected $description = 'ترحيل البيانات من قاعدة ERP القديمة (MySQL مثل Stocky/POS) إلى جداول غاز اليمين';

    public function handle(): int
    {
        $connection = (string) $this->option('connection');
        $service = new LegacyErpImportService($connection);

        try {
            $service->assertLegacySchema();
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $this->line('راجع LEGACY_ERP_* في .env أو استخدم LEGACY_ERP_DRIVER=sqlite لملف تجريبي.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info($dryRun ? 'وضع التجربة (dry-run)' : 'بدء الترحيل من ERP…');

        $counts = $service->run(fn (string $m) => $this->line($m), $dryRun);

        $this->newLine();
        $this->table(array_keys($counts), [array_values($counts)]);

        if (! $dryRun) {
            $this->info('اكتمل. سجّل الدفعة في import_audit.');
        }

        return self::SUCCESS;
    }
}
