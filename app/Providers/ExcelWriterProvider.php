<?php


namespace App\Providers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelWriterProvider
{
    private $filePath;
    private $spreadsheet;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->loadSpreadsheet();
    }

    private function loadSpreadsheet()
    {
        $this->spreadsheet = IOFactory::load($this->filePath);
    }

    /**
     * Method to append a new row in an excel file.
     * @param array $data
     * @return void
     */
    public function appendRow($data)
    {
        $sheet = $this->spreadsheet->getActiveSheet();
        $lastRow = $sheet->getHighestRow() + 1;
        $sheet->fromArray($data, null, 'A' . $lastRow);
    }

    public function save()
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($this->filePath);
    }

    public function download()
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
    }
}
?>
