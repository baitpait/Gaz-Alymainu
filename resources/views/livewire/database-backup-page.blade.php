<div>
    <div class="mb-6">
        <h1 class="text-xl font-bold text-[#1E293B]">نسخ احتياطي لقاعدة البيانات</h1>
        <p class="text-sm text-gray-400 mt-0.5">
            اتصال حالي: <span class="font-medium text-[#1E293B]">{{ $driverLabel }}</span>
            — الملفات تُحفظ في <code class="text-xs bg-gray-100 px-1 rounded" dir="ltr">{{ $backupDir }}</code> (خارج Git)
        </p>
    </div>

    @if($statusMessage !== '')
    <div class="card px-4 py-3 mb-5 text-sm {{ $statusType === 'error' ? 'bg-red-50 text-red-700 border border-red-100' : 'bg-green-50 text-green-800 border border-green-100' }}">
        {{ $statusMessage }}
    </div>
    @endif

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3 mb-6">
        @if($driverName === 'sqlite')
        <div class="card p-5 flex flex-col">
            <h2 class="text-sm font-bold text-[#1E293B] mb-1">نسخة SQLite كاملة</h2>
            <p class="text-xs text-gray-500 flex-1 mb-4">نسخ ملف القاعدة بالكامل — مناسب للتطوير المحلي والاسترجاع السريع.</p>
            <button type="button" wire:click="createSqliteBackup" wire:loading.attr="disabled"
                    class="btn btn-primary w-full text-sm">
                <span wire:loading.remove wire:target="createSqliteBackup">تنزيل نسخة SQLite</span>
                <span wire:loading wire:target="createSqliteBackup">جاري الإنشاء...</span>
            </button>
        </div>
        @endif

        @if(in_array($driverName, ['mysql', 'mariadb'], true))
        <div class="card p-5 flex flex-col">
            <h2 class="text-sm font-bold text-[#1E293B] mb-1">نسخة MySQL كاملة</h2>
            <p class="text-xs text-gray-500 flex-1 mb-4">هيكل الجداول + البيانات عبر mysqldump — للإنتاج والاسترجاع الكامل.</p>
            <button type="button" wire:click="createMysqlFullBackup" wire:loading.attr="disabled"
                    class="btn btn-primary w-full text-sm">
                <span wire:loading.remove wire:target="createMysqlFullBackup">تنزيل نسخة SQL كاملة</span>
                <span wire:loading wire:target="createMysqlFullBackup">جاري الإنشاء...</span>
            </button>
        </div>
        @endif

        <div class="card p-5 flex flex-col">
            <h2 class="text-sm font-bold text-[#1E293B] mb-1">بيانات فقط (INSERT)</h2>
            <p class="text-xs text-gray-500 flex-1 mb-4">أوامر INSERT بدون CREATE TABLE — للاستيراد بعد <code class="text-[10px]">migrate</code> على MySQL.</p>
            <button type="button" wire:click="createMysqlDataBackup" wire:loading.attr="disabled"
                    class="btn btn-secondary w-full text-sm">
                <span wire:loading.remove wire:target="createMysqlDataBackup">تنزيل بيانات INSERT</span>
                <span wire:loading wire:target="createMysqlDataBackup">جاري التصدير...</span>
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-4 py-3 border-b border-[#E2E8F0]">
            <h2 class="text-sm font-bold text-[#1E293B]">آخر النسخ على الخادم</h2>
            <p class="text-xs text-gray-400 mt-0.5">احتفظ بنسخة على قرص خارجي أو سحابة — لا تُرفع لـ GitHub.</p>
        </div>
        <div class="overflow-x-auto [-webkit-overflow-scrolling:touch]">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>الملف</th>
                        <th>النوع</th>
                        <th>الحجم</th>
                        <th>التاريخ</th>
                        <th class="w-28"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $item)
                    <tr>
                        <td class="font-mono text-xs" dir="ltr">{{ $item['filename'] }}</td>
                        <td class="text-gray-500 text-xs">
                            @if($item['type'] === 'sqlite') SQLite
                            @elseif($item['type'] === 'mysql_full') MySQL كامل
                            @else INSERT فقط
                            @endif
                        </td>
                        <td class="text-gray-500 text-xs" dir="ltr">{{ $item['size_human'] }}</td>
                        <td class="text-gray-500 text-xs" dir="ltr">{{ $item['created_at'] }}</td>
                        <td>
                            <a href="{{ route('database-backup.download', $item['filename']) }}"
                               class="btn btn-ghost py-1 px-2 text-xs text-[#1B6CA8] hover:bg-amber-50"
                               style="text-decoration:none;">تنزيل</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-12 text-gray-300 text-sm">لا توجد نسخ بعد — أنشئ نسخة من الأزرار أعلاه.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-5 text-xs text-gray-400 leading-relaxed">
        <p class="font-semibold text-gray-500 mb-1">تنبيهات</p>
        <ul class="list-disc list-inside space-y-1">
            <li>لا تشغّل <code>migrate:fresh</code> على بيئة فيها بيانات حقيقية دون نسخة.</li>
            <li>على الإنتاج قد تحتاج <code>MYSQLDUMP_PATH</code> في <code>.env</code> إذا لم يكن mysqldump في المسار الافتراضي.</li>
            <li>راجع أيضاً <code>docs/DATABASE_BACKUP_AND_RESTORE_AR.md</code>.</li>
        </ul>
    </div>
</div>
