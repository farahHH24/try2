<?php

declare(strict_types=1);

function extract_text_from_pdf(string $filePath): string
{
    if (!is_readable($filePath)) {
        throw new RuntimeException('Uploaded CV could not be read.');
    }

    // Prefer pdftotext if available.
    $pdftotextPath = trim((string) shell_exec('which pdftotext'));
    if (!empty($pdftotextPath)) {
        $outputPath = tempnam(sys_get_temp_dir(), 'cvtext_');
        $command = escapeshellcmd($pdftotextPath) . ' -layout ' . escapeshellarg($filePath) . ' ' . escapeshellarg($outputPath);
        exec($command, $dummy, $code);

        if ($code === 0 && is_readable($outputPath)) {
            $text = file_get_contents($outputPath) ?: '';
            unlink($outputPath);
            return trim($text);
        }
    }

    // Fallback to Python + PyPDF2 if available.
    $python = trim((string) shell_exec('which python3'));
    if (!empty($python)) {
        $escapedPath = escapeshellarg($filePath);
        $script = <<<PY
import sys
try:
    import PyPDF2
except ImportError:
    sys.exit(10)

from pathlib import Path
path = Path($escapedPath)
if not path.exists():
    sys.exit(1)

reader = PyPDF2.PdfReader(str(path))
text_parts = []
for page in reader.pages:
    text_parts.append(page.extract_text() or "")

print("\\n".join(text_parts))
PY;
        $command = $python . " - <<'PY'\n" . $script . "\nPY";
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        if ($returnCode === 0) {
            return trim(implode("\n", $output));
        }
    }

    throw new RuntimeException('Unable to extract text from the PDF. Install pdftotext or PyPDF2 for Python.');
}
