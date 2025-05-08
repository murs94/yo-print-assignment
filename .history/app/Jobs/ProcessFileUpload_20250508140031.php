<?php

namespace App\Jobs;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Writer;
use Illuminate\Support\Facades\DB;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $upload;

    public function __construct(Upload $upload)
    {
        $this->upload = $upload;
    }

    public function handle()
    {
        try {
            $this->upload->update(['status' => 'processing']);

            // Get the file path
            $filePath = Storage::path($this->upload->path);

            // Clean UTF-8 and create a new file
            $cleanedFilePath = $this->cleanUtf8($filePath);

            // Process the cleaned file
            $this->processFile($cleanedFilePath);

            // Update status to completed
            $this->upload->update(['status' => 'completed']);

            // Clean up temporary files
            if ($cleanedFilePath !== $filePath) {
                unlink($cleanedFilePath);
            }
        } catch (\Exception $e) {
            $this->upload->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function cleanUtf8($filePath)
    {
        // Read the original file
        $content = file_get_contents($filePath);

        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Convert to UTF-8 if not already
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content));
        }

        // Remove any non-UTF-8 characters
        $content = preg_replace('/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]|[\x00-\x7F][\x80-\xBF]+|[\xC0-\xDF](?![\x80-\xBF])|[\xE0-\xEF](?![\x80-\xBF]{2})|[\xF0-\xF7](?![\x80-\xBF]{3})/', '', $content);

        // Create a new file with cleaned content
        $cleanedFilePath = $filePath . '.cleaned';
        file_put_contents($cleanedFilePath, $content);

        return $cleanedFilePath;
    }

    protected function processFile($filePath)
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        // Get the header row
        $headers = $csv->getHeader();
        $records = iterator_to_array($csv->getRecords());
        $total = count($records);
        $processed = 0;

        foreach ($records as $record) {
            $uniqueKey = $record['UNIQUE_KEY'] ?? null;
            if (!$uniqueKey) {
                continue; // skip if missing
            }
            $this->upsertRecord($uniqueKey, $record);
            $processed++;
            // Update progress every 5 records or on last record
            if ($total > 0 && ($processed % 5 === 0 || $processed === $total)) {
                $progress = intval(($processed / $total) * 100);
                $this->upload->update(['progress' => $progress]);
            }
        }
        // Ensure progress is 100% at the end
        $this->upload->update(['progress' => 100]);
    }

    protected function upsertRecord($uniqueKey, $record)
    {
        $data = [
            'unique_key' => $uniqueKey,
            'product_title' => $record['PRODUCT_TITLE'] ?? null,
            'product_description' => $record['PRODUCT_DESCRIPTION'] ?? null,
            'style#' => $record['STYLE#'] ?? null,
            'sanmar_mainframe_color' => $record['SANMAR_MAINFRAME_COLOR'] ?? null,
            'size' => $record['SIZE'] ?? null,
            'color_name' => $record['COLOR_NAME'] ?? null,
            'piece_price' => $record['PIECE_PRICE'] ?? null,
            'updated_at' => now(),
        ];
        DB::table('items')->updateOrInsert(
            ['unique_key' => $uniqueKey],
            $data
        );
    }
}
