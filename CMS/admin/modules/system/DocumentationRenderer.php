<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class DocumentationRenderer
{
    /** @var \Closure(string, string): string */
    private readonly \Closure $linkResolver;

    /**
     * @param callable(string, string): string $linkResolver
     */
    public function __construct(callable $linkResolver)
    {
        $this->linkResolver = \Closure::fromCallable($linkResolver);
    }

    public function renderDocument(string $contents, string $extension, string $currentDocument): string
    {
        return strtolower($extension) === 'csv'
            ? $this->renderCsv($contents)
            : $this->renderMarkdown($contents, $currentDocument);
    }

    private function renderMarkdown(string $markdown, string $currentDocument): string
    {
        $lines = preg_split('/\R/', str_replace(["\r\n", "\r"], "\n", $markdown)) ?: [];
        $html = '';
        $paragraph = [];
        $listType = null;
        $tableLines = [];
        $inCodeBlock = false;
        $codeLines = [];

        foreach ($lines as $line) {
            $trimmed = rtrim($line);
            $clean = trim($trimmed);

            if (preg_match('/^```\s*([^`]*)$/', $clean) === 1) {
                $html .= $this->flushTable($tableLines, $currentDocument);
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);

                if ($inCodeBlock) {
                    $html .= '<pre class="bg-dark-lt p-3 rounded overflow-auto"><code>'
                        . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES)
                        . '</code></pre>';
                    $inCodeBlock = false;
                    $codeLines = [];
                } else {
                    $inCodeBlock = true;
                }

                continue;
            }

            if ($inCodeBlock) {
                $codeLines[] = $trimmed;
                continue;
            }

            if ($clean === '') {
                $html .= $this->flushTable($tableLines, $currentDocument);
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                continue;
            }

            if (preg_match('/^\|.+\|$/', $clean) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                $tableLines[] = $clean;
                continue;
            }

            if ($tableLines !== []) {
                $html .= $this->flushTable($tableLines, $currentDocument);
            }

            if (preg_match('/^(#{1,6})\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                $html .= $this->closeList($listType);
                $level = strlen($matches[1]);
                $html .= sprintf(
                    '<h%d class="mt-4 mb-3">%s</h%d>',
                    $level,
                    $this->renderInline(trim($matches[2]), $currentDocument),
                    $level
                );
                continue;
            }

            if (preg_match('/^[-*]\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                if ($listType !== 'ul') {
                    $html .= $this->closeList($listType);
                    $html .= '<ul class="mb-3">';
                    $listType = 'ul';
                }
                $html .= '<li>' . $this->renderInline(trim($matches[1]), $currentDocument) . '</li>';
                continue;
            }

            if (preg_match('/^\d+\.\s+(.+)$/', $clean, $matches) === 1) {
                $html .= $this->flushParagraph($paragraph, $currentDocument);
                if ($listType !== 'ol') {
                    $html .= $this->closeList($listType);
                    $html .= '<ol class="mb-3">';
                    $listType = 'ol';
                }
                $html .= '<li>' . $this->renderInline(trim($matches[1]), $currentDocument) . '</li>';
                continue;
            }

            $html .= $this->closeList($listType);
            $paragraph[] = $clean;
        }

        if ($inCodeBlock) {
            $html .= '<pre class="bg-dark-lt p-3 rounded overflow-auto"><code>'
                . htmlspecialchars(implode("\n", $codeLines), ENT_QUOTES)
                . '</code></pre>';
        }

        $html .= $this->flushTable($tableLines, $currentDocument);
        $html .= $this->flushParagraph($paragraph, $currentDocument);
        $html .= $this->closeList($listType);

        return $html;
    }

    /**
     * @param array<int, string> $paragraph
     */
    private function flushParagraph(array &$paragraph, string $currentDocument): string
    {
        if ($paragraph === []) {
            return '';
        }

        $text = implode(' ', $paragraph);
        $paragraph = [];

        if ($text === '---') {
            return '<hr class="my-4">';
        }

        return '<p>' . $this->renderInline($text, $currentDocument) . '</p>';
    }

    private function closeList(?string &$listType): string
    {
        if ($listType === null) {
            return '';
        }

        $tag = $listType;
        $listType = null;

        return '</' . $tag . '>';
    }

    /**
     * @param array<int, string> $tableLines
     */
    private function flushTable(array &$tableLines, string $currentDocument): string
    {
        if ($tableLines === []) {
            return '';
        }

        $rows = [];
        foreach ($tableLines as $line) {
            $trimmed = trim($line);
            $trimmed = trim($trimmed, '|');
            $rows[] = array_map('trim', explode('|', $trimmed));
        }

        $tableLines = [];
        if ($rows === []) {
            return '';
        }

        $header = $rows[0];
        $bodyRows = array_slice($rows, 1);

        if ($bodyRows !== [] && $this->isTableSeparator($bodyRows[0])) {
            $bodyRows = array_slice($bodyRows, 1);
        }

        $html = '<div class="table-responsive mb-4"><table class="table table-bordered table-striped table-sm">';
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . $this->renderInline($cell, $currentDocument) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($bodyRows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $this->renderInline($cell, $currentDocument) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * @param array<int, string> $row
     */
    private function isTableSeparator(array $row): bool
    {
        foreach ($row as $cell) {
            if (preg_match('/^:?-{3,}:?$/', trim($cell)) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function renderInline(string $text, string $currentDocument): string
    {
        $rendered = htmlspecialchars($text, ENT_QUOTES);

        $rendered = preg_replace_callback('/\[([^\]]+)\]\(([^\)]+)\)/', function (array $matches) use ($currentDocument): string {
            $label = $matches[1];
            $target = html_entity_decode($matches[2], ENT_QUOTES);
            $href = ($this->linkResolver)($currentDocument, $target);
            $isExternal = $this->isExternalUrl($href);

            return '<a href="' . htmlspecialchars($href, ENT_QUOTES) . '"'
                . ($isExternal ? ' target="_blank" rel="noopener noreferrer"' : '')
                . '>' . $label . '</a>';
        }, $rendered) ?? $rendered;

        $rendered = preg_replace_callback('/`([^`]+)`/', static function (array $matches): string {
            return '<code>' . $matches[1] . '</code>';
        }, $rendered) ?? $rendered;

        $rendered = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $rendered) ?? $rendered;
        $rendered = preg_replace('/__(.+?)__/', '<strong>$1</strong>', $rendered) ?? $rendered;

        return $rendered;
    }

    private function isExternalUrl(string $url): bool
    {
        return preg_match('#^https?://#i', $url) === 1;
    }

    private function renderCsv(string $contents): string
    {
        $lines = preg_split('/\R/', trim($contents)) ?: [];
        if ($lines === []) {
            return '<div class="text-secondary">Keine CSV-Inhalte vorhanden.</div>';
        }

        $rows = array_map(static fn (string $line): array => str_getcsv($line), $lines);
        $header = array_shift($rows) ?: [];

        $html = '<div class="table-responsive"><table class="table table-bordered table-striped table-sm">';
        $html .= '<thead><tr>';
        foreach ($header as $cell) {
            $html .= '<th>' . htmlspecialchars((string) $cell, ENT_QUOTES) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }
}
