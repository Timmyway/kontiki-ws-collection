<?php


namespace App\Providers;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelCreatorProvider
{
    private $filePath;
    private $headers;

    public function __construct($filePath, $headers)
    {
        $this->filePath = $filePath;
        $this->headers = $headers;
    }

    public function createFile()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sheet1');

        // Set the headers
        $headerColumn = 'A';
        foreach ($this->headers as $header) {
            $sheet->setCellValue($headerColumn . '1', $header);
            $headerColumn++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->filePath);
    }
}
?>
