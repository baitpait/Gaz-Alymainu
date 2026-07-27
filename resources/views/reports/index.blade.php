<x-layouts.app title="مركز التقارير">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#3D3D3D]">مركز التقارير</h1>
        <p class="text-sm text-gray-500 mt-1">تقارير مالية حسب الفترة — معاينة على الشاشة وتصدير PDF و CSV.</p>
    </div>

    @php
    $cards = [
        ['route' => 'reports.financial-period', 'title' => 'لوحة الفترة المالية', 'desc' => 'ملخص مبيعات ومشتريات وصافي نقدي لكل عملة', 'color' => 'border-[#C9A227]'],
        ['route' => 'reports.activity-log', 'title' => 'سجل النشاط المالي', 'desc' => 'كل الحركات المالية في جدول زمني واحد', 'color' => 'border-gray-500'],
        ['route' => 'reports.cashflow', 'title' => 'كشف التدفق النقدي', 'desc' => 'دفعات العملاء والموردين والمصروفات في فترة واحدة', 'color' => 'border-teal-400'],
        ['route' => 'reports.supplier-payments', 'title' => 'دفعات الموردين', 'desc' => 'تفصيل كل دفعة مورد بين تاريخين', 'color' => 'border-purple-400'],
        ['route' => 'reports.expenses', 'title' => 'المصروفات', 'desc' => 'تفصيل المصروفات بين تاريخين', 'color' => 'border-red-400'],
        ['route' => 'reports.salaries', 'title' => 'الرواتب', 'desc' => 'رواتب الموظفين حسب شهر الفترة — مستقل عن المصروفات', 'color' => 'border-amber-400'],
        ['route' => 'reports.profit-loss', 'title' => 'الربح والخسارة', 'desc' => 'صافي الربح لكل عملة — فواتير مبيعات ومشتريات', 'color' => 'border-green-500'],
        ['route' => 'reports.profit-loss-cash', 'title' => 'الربح والخسارة (بدون دين)', 'desc' => 'نقدي — دفعات عملاء وموردين فقط', 'color' => 'border-emerald-400'],
        ['route' => 'reports.profit-loss-ils', 'title' => 'الربح والخسارة بالشيكل', 'desc' => 'مجمّع بأسعار بنك إسرائيل', 'color' => 'border-lime-400'],
        ['route' => 'reports.purchase-orders', 'title' => 'تقرير المشتريات', 'desc' => 'أوامر شراء صادرة مع حالة الدفع', 'color' => 'border-indigo-400'],
        ['route' => 'reports.supplier-receivables-aging', 'title' => 'أعمار ذمم الموردين', 'desc' => 'متبقٍ لكل مورد حسب أيام التأخير', 'color' => 'border-violet-400'],
        ['route' => 'reports.supplier-payables-summary', 'title' => 'ملخص ذمم الموردين', 'desc' => 'متبقٍ لكل مورد حتى تاريخ — بدون عمرية', 'color' => 'border-indigo-400'],
        ['route' => 'reports.aggregated-supplier-statements', 'title' => 'كشوف الموردين المجمّعة', 'desc' => 'ملخص كشف حساب لكل مورد ضمن الفترة', 'color' => 'border-blue-400'],
        ['route' => 'reports.supplier-adjustments', 'title' => 'تسويات الموردين', 'desc' => 'تسويات ذمة الموردين في فترة', 'color' => 'border-fuchsia-400'],
    ];
    @endphp

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($cards as $card)
        <a href="{{ route($card['route']) }}" wire:navigate
           class="card p-5 border-r-4 {{ $card['color'] }} hover:shadow-md transition block"
           style="text-decoration:none;">
            <h2 class="text-lg font-bold text-[#3D3D3D] mb-1">{{ $card['title'] }}</h2>
            <p class="text-sm text-gray-500">{{ $card['desc'] }}</p>
        </a>
        @endforeach
    </div>

    @php
    $gasCards = [
        ['route' => 'reports.gas-sales', 'title' => 'مبيعات الغاز', 'desc' => 'عمليات البيع نقدي/على الحساب حسب السائق والصنف', 'color' => 'border-green-500'],
        ['route' => 'reports.gas-collections', 'title' => 'التحصيلات', 'desc' => 'تحصيلات نقدية وشيكات حسب السائق والفترة', 'color' => 'border-blue-400'],
        ['route' => 'reports.gas-driver-cash', 'title' => 'صندوق السائق وتسوية الكاش', 'desc' => 'نقدي + تحصيل − مسحوب = الرصيد لكل سائق', 'color' => 'border-[#E8590C]'],
        ['route' => 'reports.gas-stock-balances', 'title' => 'أرصدة المخزون', 'desc' => 'الكمية الحالية لكل صنف في كل مخزن/سيارة', 'color' => 'border-teal-400'],
        ['route' => 'reports.gas-stock-movements', 'title' => 'حركات المخزون', 'desc' => 'شراء/تحميل/إرجاع/تحويل/بيع/تسوية في فترة', 'color' => 'border-indigo-400'],
        ['route' => 'reports.gas-driver-performance', 'title' => 'أداء السائقين', 'desc' => 'ملخص لكل سائق: كميات ومبيعات وتحصيلات', 'color' => 'border-[#C9A227]'],
    ];
    @endphp

    <h2 class="text-lg font-bold text-[#3D3D3D] mt-10 mb-3">تقارير الغاز والمخزون</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        @foreach($gasCards as $card)
        <a href="{{ route($card['route']) }}" wire:navigate
           class="card p-5 border-r-4 {{ $card['color'] }} hover:shadow-md transition block"
           style="text-decoration:none;">
            <h2 class="text-lg font-bold text-[#3D3D3D] mb-1">{{ $card['title'] }}</h2>
            <p class="text-sm text-gray-500">{{ $card['desc'] }}</p>
        </a>
        @endforeach
    </div>

    <div class="mt-8 card p-4 text-sm text-gray-500">
        <p class="font-semibold text-[#3D3D3D] mb-2">كشوف حساب فردية</p>
        <p>كشف حساب عميل أو مورد (مع PDF) متاح من صفحة العميل/المورد — وليس من هنا.</p>
    </div>
</x-layouts.app>
