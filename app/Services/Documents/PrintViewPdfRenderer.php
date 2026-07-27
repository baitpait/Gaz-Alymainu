<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the same Blade print templates as the browser print preview, then captures
 * them with headless Chrome (emulateMedia=print) for a pixel-perfect PDF match.
 */
class PrintViewPdfRenderer
{
    private ?string $resolvedChromePath = null;

    private bool $chromePathResolved = false;
    public function isAvailable(): bool
    {
        return $this->resolveChromePath() !== null;
    }

    /** @return array<string, string> */
    public function diagnostics(): array
    {
        $node = config('browsershot.node_binary', 'node');
        $nodeVersion = Process::run([$node, '--version']);
        $chromePath = $this->resolveChromePath();
        $chromeProbe = $chromePath !== null
            ? Process::run([$chromePath, '--version'])
            : null;

        return [
            'node_binary' => $node,
            'node_version' => $nodeVersion->successful() ? trim($nodeVersion->output()) : trim($nodeVersion->errorOutput()),
            'node_ok' => $nodeVersion->successful() ? 'yes' : 'no',
            'puppeteer_cache_dir' => (string) config('browsershot.puppeteer_cache_dir'),
            'chrome_path' => $chromePath ?? '(not resolved)',
            'chrome_ok' => $chromeProbe?->successful() ? 'yes' : 'no',
            'chrome_version' => $chromeProbe?->successful()
                ? trim($chromeProbe->output())
                : trim((string) $chromeProbe?->errorOutput()),
            'no_sandbox' => config('browsershot.no_sandbox', false) ? 'yes' : 'no',
        ];
    }

    /** @param array<string, mixed> $data */
    public function streamFromView(string $view, array $data, string $filename): Response
    {
        set_time_limit((int) config('browsershot.php_timeout', 120));

        $html = view($view, array_merge($data, ['exportMode' => 'pdf']))->render();
        $html = $this->inlineLocalPublicAssets($html);

        try {
            $pdf = $this->configureShot(Browsershot::html($html))->pdf();
        } catch (\Throwable $e) {
            Log::error('PrintViewPdfRenderer failed', [
                'view' => $view,
                'diagnostics' => $this->diagnostics(),
                'exception' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                'تعذّر إنشاء PDF من قالب الطباعة. تأكد من تثبيت Node و Puppeteer على السيرفر.',
                previous: $e
            );
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Headless Chrome must not HTTP-fetch assets from the same php artisan serve worker
     * (single-threaded) or the PDF request deadlocks until max_execution_time.
     */
    private function inlineLocalPublicAssets(string $html): string
    {
        $logoPath = public_path('branding/logo.png');
        if (! is_readable($logoPath)) {
            return $html;
        }

        $dataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));

        $replaced = preg_replace(
            '#src="[^"]*branding/logo\.png(?:\?[^"]*)?"#',
            'src="'.$dataUri.'"',
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    private function configureShot(Browsershot $shot): Browsershot
    {
        $tmpDir = (string) config('browsershot.temp_path', storage_path('app/browsershot-tmp'));
        if ($tmpDir !== '' && ! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $shot
            ->setCustomTempPath($tmpDir)
            ->showBackground()
            ->emulateMedia('print')
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->setDelay(config('browsershot.pdf_delay_ms', 1500))
            ->setNodeBinary(config('browsershot.node_binary', 'node'))
            ->setNodeModulePath(config('browsershot.node_modules_path', base_path('node_modules')))
            ->setEnvironmentOptions($this->puppeteerEnvironment())
            ->addChromiumArguments([
                'disable-dev-shm-usage',
                'disable-gpu',
            ]);

        $chrome = $this->resolveChromePath();
        if ($chrome !== null) {
            $shot->setChromePath($chrome);
        }

        if (config('browsershot.no_sandbox', false)) {
            $shot->noSandbox();
        }

        return $shot;
    }

    /** @return array<string, string> */
    private function puppeteerEnvironment(): array
    {
        $cacheDir = (string) config('browsershot.puppeteer_cache_dir');

        if ($cacheDir !== '' && ! is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }

        return array_filter([
            'PUPPETEER_CACHE_DIR' => $cacheDir !== '' ? $cacheDir : null,
            'HOME' => storage_path('app'),
        ]);
    }

    private function resolveChromePath(): ?string
    {
        if ($this->chromePathResolved) {
            return $this->resolvedChromePath;
        }

        $this->chromePathResolved = true;

        $configured = config('browsershot.chrome_path');
        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $this->resolvedChromePath = $configured;
        }

        $node = config('browsershot.node_binary', 'node');
        $modules = config('browsershot.node_modules_path', base_path('node_modules'));

        $script = <<<'JS'
import puppeteer from 'puppeteer';
console.log(await puppeteer.executablePath());
JS;

        $result = Process::path(base_path())
            ->env(array_merge(['NODE_PATH' => $modules], $this->puppeteerEnvironment()))
            ->run([$node, '--input-type=module', '-e', $script]);

        if (! $result->successful()) {
            Log::warning('Puppeteer executablePath probe failed', [
                'output' => trim($result->output()),
                'error' => trim($result->errorOutput()),
                'node_binary' => $node,
                'puppeteer_cache_dir' => config('browsershot.puppeteer_cache_dir'),
            ]);

            return $this->resolvedChromePath = null;
        }

        $path = trim($result->output());

        return $this->resolvedChromePath = ($path !== '' && file_exists($path)) ? $path : null;
    }
}
