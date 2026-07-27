@props([
    'pdfUrl',
])

@if(!($exportMode ?? false))
<div class="print-actions">
    <button type="button" class="print-btn" onclick="window.print()">🖨 طباعة</button>
    <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="print-btn print-btn-pdf">⬇ PDF</a>
</div>
<style>
  .print-actions {
    position: fixed;
    bottom: 32px;
    left: 32px;
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    z-index: 100;
  }
  .print-actions .print-btn {
    position: static;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 12px 24px;
    border: none;
    border-radius: 50px;
    font-family: inherit;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.2;
    cursor: pointer;
    text-decoration: none;
    white-space: nowrap;
    background: #1B6CA8;
    color: #fff;
    box-shadow: 0 4px 16px rgba(201, 162, 39, 0.4);
  }
  .print-actions .print-btn:hover {
    background: #b08f20;
  }
  .print-actions .print-btn-pdf {
    background: #1E293B;
    box-shadow: 0 4px 16px rgba(61, 61, 61, 0.35);
  }
  .print-actions .print-btn-pdf:hover {
    background: #2a2a2a;
  }
  @media print {
    .print-actions { display: none !important; }
  }
</style>
@endif
