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
    foreach ($this->years as $key => $value) {
      $width = $this->getColWidth($value);
      $bgCol = $this->getColBgColor($key);
      $row .= '<div
      class="header-col ' . $bgCol . '"
      style="width: ' . $width . ';">' .
      $key . '</div>';
    }
    $row .= '<div class="last-header-col"></div>';
    return $row;
  }

  protected function getMonthRow()
  {
    return '<div class="header-col bg-warning">Month</div>';
  }

  protected function getWeekRow()
  {
    return '<div class="header-col bg-info">Week</div>';
  }

  private function getColWidth($val)
  {
    $width =  $val * $this->unitSize;
    $colWidth = $width . 'px';
    return $colWidth;
  }

  private function getColBgColor($val)
  {
    // even $val
    if ($val % 2) return 'bg-info';

    // odd $val
    return 'bg-danger';
  }

}
 ?>
