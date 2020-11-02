<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\gantt;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class GanttColumnHeader extends \yii\base\Component
{
  private $_headerRow;
  public $unitSize = 14;
  public $years = [];
  public $months = [];
  public $weeks = [];

  public function getHeaderRow()
  {
    return $this->getYearRow() . $this->getMonthRow() . $this->getWeekRow();
  }

  protected function getYearRow()
  {
    $row = '';
    foreach ($this->years as $year => $units) {
      $row .= $this->generateRow( $units, $year, 'bg-info', 'bg-danger');
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  protected function getMonthRow()
  {
    $row = '';
    foreach ($this->months as $yearMonth => $units) {
      // explode 'YYYY-mm' to 'mm'
      $value = explode('-', $yearMonth)[1];
      $row .= $this->generateRow( $units, $value, 'bg-success', 'bg-warning');
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  protected function getWeekRow()
  {
    $row = '';
    foreach ($this->weeks as $week) {
      $row .= $this->generateRow( 1, $week, 'bg-grey', 'bg-default');
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  private function generateRow($widthVal, $value, $bgEven, $bgOdd)
  {
    $width = $this->getColWidth($widthVal);
    $bgCol = $this->getColBgColor($value, $bgEven, $bgOdd);
    $options['class'] = 'header-col ' . $bgCol;
    $options['style'] = 'width: ' . $width;
    return Html::tag('div', $value, $options);
  }


  private function getColWidth($val)
  {
    $width =  $val * $this->unitSize;
    $colWidth = $width . 'px';
    return $colWidth;
  }

  private function getColBgColor($val, $bgEven, $bgOdd)
  {
    // even $val
    if ($val % 2) return $bgEven;

    // odd $val
    return $bgOdd;
  }

}
 ?>
