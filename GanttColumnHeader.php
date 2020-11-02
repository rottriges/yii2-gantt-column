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
  public $unitSize;
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
      $row .= '<div class="header-col" style="width: ' . $width . ';">' . $key . '</div>';
    }
    return $row;
  }

  protected function getMonthRow()
  {
    return '<div class="bg-warning">Month</div>';
  }

  protected function getWeekRow()
  {
    return '<div class="bg-info">Week</div>';
  }

  private function getColWidth($val)
  {
    $width =  $val * $this->unitSize;
    $colWidth = $width . 'px';
    return $colWidth;
  }

}
 ?>
