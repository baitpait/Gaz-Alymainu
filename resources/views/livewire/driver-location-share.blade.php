<div class="max-w-md mx-auto">
    <div class="mb-5">
        <h1 class="text-xl font-bold text-[#3D3D3D]">موقعي</h1>
        <p class="text-sm text-gray-500 mt-1">المشاركة تلقائية — لا يمكن إيقافها من هنا.</p>
    </div>

    <div class="card p-5 space-y-3">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full border-[3px] border-green-500 bg-green-50 text-green-600 flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-green-600">جاري الإرسال تلقائياً</p>
                <p class="text-xs text-gray-500">طالما التطبيق مفتوح على الجهاز</p>
            </div>
        </div>

        <p class="text-sm text-gray-600 leading-relaxed">
            موقعك يُرسل للإدارة كل حوالي 30 ثانية من أي شاشة (المبيعات / التحصيل / المصروفات).
            لا يوجد زر إيقاف للسائق.
        </p>

        <div class="bg-amber-50 rounded-lg px-3 py-2 text-xs text-amber-800 leading-relaxed">
            إذا أُغلقت الشاشة أو أُغلق المتصفح: يتوقف التحديث الحيّ، ويبقى
            <strong>آخر موقع معروف</strong> على خريطة الإدارة بحالة «قديم» حتى تعود للفتح.
        </div>

        <a href="{{ route('pos.index') }}" wire:navigate class="btn btn-primary w-full text-center" style="text-decoration:none;">
            العودة لنقطة البيع
        </a>
    </div>
</div>
