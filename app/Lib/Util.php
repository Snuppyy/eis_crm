<?php

namespace App\Lib;

class Util
{
    public static function arraysEqualRecursive($array1, $array2)
    {
        if (!is_array($array1) || !is_array($array2)) {
            return $array1 === $array2;
        }

        foreach (array_keys($array1) as $key) {
            if (!isset($array2[$key]) || !static::arraysEqualRecursive($array1[$key], $array2[$key])) {
                return false;
            }
        }

        foreach (array_keys($array2) as $key) {
            if (!isset($array1[$key]) || !static::arraysEqualRecursive($array2[$key], $array1[$key])) {
                return false;
            }
        }

        return true;
    }

    public static function clonePhpSpreadsheetRow($sheet, $row_from, $row_to)
    {
        $sheet->insertNewRowBefore($row_to, 1);
        $sheet->getRowDimension($row_to)->setRowHeight($sheet->getRowDimension($row_from)->getRowHeight());
        $lastColumn = $sheet->getHighestColumn();
        ++$lastColumn;
        for ($c = 'A'; $c != $lastColumn; ++$c) {
            $cell_from = $sheet->getCell($c.$row_from);
            $cell_to = $sheet->getCell($c.$row_to);
            $cell_to->setXfIndex($cell_from->getXfIndex());
            $cell_to->setValue($cell_from->getValue());
        }
    }
}
