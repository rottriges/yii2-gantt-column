<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\gantt;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

class GanttColumnHeader extends \yii\base\Component
{
  private $_headerRow;
  public $unitSize = 14;
  public $years = [];
  public $months = [];
  public $weeks = [];

  /**
   * @var array the english month settings.
   */
  private static $_months = [
      'monthsLong' => [
          1 => 'January',
          2 => 'February',
          3 => 'March',
          4 =>'April',
          5 => 'May',
          6 => 'June',
          7 => 'July',
          8 => 'August',
          9 =>'September',
          10 => 'October',
          11 =>'November',
          12 =>'December',
      ],
      'monthsShort' => [
          1 => 'Jan',
          2 => 'Feb',
          3 => 'Mar',
          4 =>'Apr',
          5 => 'May',
          6 => 'Jun',
          7 => 'Jul',
          8 => 'Aug',
          9 =>'Sep',
          10 => 'Oct',
          11 =>'Nov',
          12 =>'Dec',
      ],
  ];

  public function init()
  {
    return parent::init();
  }

  public static function registerTranslations()
  {
      $i18n = Yii::$app->i18n;
      $i18n->translations['ganttColumn'] = [
          'class' => 'yii\i18n\PhpMessageSource',
          'sourceLanguage' => 'en-US',
          'basePath' => __DIR__.'/messages',
      ];
  }

  public static function t($category, $message, $params = [], $language = null)
  {
    return Yii::t( $category, $message, $params, $language);
  }

  public function getHeaderRow()
  {
    return $this->getYearRow() . $this->getMonthRow() . $this->getWeekRow();
  }

  protected function getYearRow()
  {
    $row = '';
    $bgOdd = 'bg-info';
    $bgEven = 'bg-danger';
    $i = 0;
    foreach ($this->years as $year => $units) {
      $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
      $row .= $this->generateRow( $units, $year, $bgColor);
      $i++;
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  protected function getMonthRow()
  {
    $row = '';
    $bgOdd = 'bg-success';
    $bgEven = 'bg-warning';
    $i = 0;
    // GanttColumnHeader::registerTranslations();
    foreach ($this->months as $yearMonth => $units) {
      $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
      // explode 'YYYY-mm' to 'mm'
      $value = intval(explode('-', $yearMonth)[1]);
      $value = self::$_months['monthsShort'][$value];
      $value = self::t('ganttColumn', $value);
      $row .= $this->generateRow( $units, $value, $bgColor);
      $i++;
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  protected function getWeekRow()
  {
    $row = '';
    $bgOdd = 'week bg-week-od';
    $bgEven = 'week bg-week-even';
    $i = 0;
    foreach ($this->weeks as $week) {
      $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
      $row .= $this->generateRow( 1, $week, $bgColor);
      $i++;
    }
    $row .= Html::tag('div', '', ['class' => 'last-header-col' ]);
    return $row;
  }

  private function generateRow($widthVal, $value, $bgColor)
  {
    $width = $this->getColWidth($widthVal);
    $options['class'] = 'header-col ' . $bgColor;
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
