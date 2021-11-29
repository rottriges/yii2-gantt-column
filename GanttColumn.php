<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\ganttcolumn;

use Yii;
use Closure;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\grid\DataColumn;

class GanttColumn extends DataColumn
{
  private $_header;

  /**
   * @var string the format setting for the gantt cell musst always be html.
   */
  public $format = 'html';

  /**
   * @var string the width of the gantt column (matches the CSS width property).
   * @see http://www.w3schools.com/cssref/pr_dim_width.asp
   */
    public $width;

    /**
     * @var array|Closure the configuration options for the gantt column.
     * If not set as an array, this can be passed as a callback function
     *of the signature: `function ($model, $key, $index)`, where:
     * - `$model`: _\yii\base\Model_, is the data model.
     * - `$key`: _string|object_, is the primary key value associated with the data model.
     * - `$index`: _integer_, is the zero-based index of the data model among the model array returned by [[dataProvider]].
     *
     *
     * This property is for the additional settings for gantt column options
     */
    public $ganttOptions = [];

    /**
     * @var string the startDate for ther processbar
     */
    private $_startDate;

     /**
      * @var string the endDate for ther processbar
      */
    private $_endDate;

    /**
     * @var string the dateRangeStart
     */
    private $_dateRangeStart;

     /**
      * @var string the dateRangeEnd
      */
    private $_dateRangeEnd;

    /**
     * @var string the date format
     */
    public $ganttDateFormat = 'Y-m-d';

    /**
     * @var string the size of one unit in px
     */
    public $unitSize = 17;

    /**
     * @var string the amount of all units
     */
    public $unitSum;

    /**
     * startGap
     *
     * size of the gap (weeks * units) if the startDate is greater than the dateRangeStart
     *
     * @var int size of gap
     */
    private $_startGap;

    /**
     * progressLength
     *
     * size of the progress bar in weeks * units
     *
     * @var int size of gap
     */
    private $_progressLength;

    /**
    * progress typ (primary, danger, success, warnin or info)
    */
    private $_progressType;

    /**
    * progress color string|closure
    */
    private $_progressColor;


    public function init()
    {
      parent::init();

      $this->registerTranslations();

      $this->_header = new GanttColumnHeader();
      $this->_header->unitSize = $this->unitSize;
      self::registerTranslations();

      if (!is_array($this->ganttOptions)) {
        throw new InvalidConfigException("`ganttOptions` is not an array");
      }

      if (!isset($this->ganttOptions['startAttribute']) || !isset($this->ganttOptions['endAttribute'])) {
        throw new InvalidConfigException("`startAttribute` and/or `endAttribute` not defined");
      }

      if (!isset($this->ganttOptions['dateRangeStart']) || !isset($this->ganttOptions['dateRangeStart'])) {
        throw new InvalidConfigException("`dateRangeStart` and/or `dateRangeStart` not defined");
      }

      if (!isset($this->ganttOptions['progressBarType']) ) {
        $this->ganttOptions['progressBarType'] = 'primary';
      }

      if (!isset($this->ganttOptions['progressBarColor']) ) {
        $this->ganttOptions['progressBarColor'] = '';
      }

      $this->_dateRangeStart = $this->getDateRange($this->ganttOptions['dateRangeStart']);
      $this->_dateRangeEnd = $this->getDateRange($this->ganttOptions['dateRangeEnd']);

      $this->getUnits();
      $this->unitSum = count($this->_header->weeks);

      $view = Yii::$app->getView();
      GanttViewAsset::register($view);

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



  /**
     * Renders the header cell content.
     * The default implementation simply renders [[header]].
     * This method may be overridden to customize the rendering of the header cell.
     * @return string the rendering result
     */
    protected function renderHeaderCellContent()
    {
        return $this->_header->headerRow;
    }

    /**
     * Renders a data cell.
     * @param mixed $model the data model being rendered
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data item among the item array returned by [[GridView::dataProvider]].
     * @return string the rendering result
     */
    public function renderDataCell($model, $key, $index)
    {
        if ($this->contentOptions instanceof Closure) {
            $options = call_user_func($this->contentOptions, $model, $key, $index, $this);
        } else {
            $options = $this->contentOptions;
        }
        $this->width = ($this->unitSum * $this->unitSize + 20) . 'px';
        Html::addCssStyle($options, "width:{$this->width} !important;");

        // return Html::tag('td', $this->progressBar, $options);
        return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
    }

    /**
     * Renders the data cell content.
     * @param mixed $model the data model
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data model among the models array returned by [[GridView::dataProvider]].
     * @return string the rendering result
     */
    protected function renderDataCellContent($model, $key, $index)
    {

        $this->_startDate = $this->getStartAttributeValue($model, $key, $index);
        $this->_endDate = $this->getEndAttributeValue($model, $key, $index);
        $this->setStartAndEndDate();

        if (
          !empty($this->ganttOptions['progressBarType']) &&
          $this->ganttOptions['progressBarType'] instanceof Closure
        ) {
            $this->_progressType = call_user_func($this->ganttOptions['progressBarType'], $model, $key, $index, $this);
        } else {
          $this->_progressType = $this->ganttOptions['progressBarType'];
        }

        if (
          !empty($this->ganttOptions['progressBarColor']) &&
          $this->ganttOptions['progressBarColor'] instanceof Closure
        ) {
            $this->_progressColor = call_user_func($this->ganttOptions['progressBarColor'], $model, $key, $index, $this);
        } else {
          $this->_progressColor = $this->ganttOptions['progressBarColor'];
        }

        if ($this->_startDate !== null && $this->_endDate !== null && $this->checkIfDatesInRange()) {
          return $this->getProgressBar();
        }

        return $this->grid->emptyCell;
    }

    /**
     * function checks wether start and end-date are date values or
     * one of each is a date-value and the other is an integer.
     *
     * @return boolean
     * @throws InvalidConfigException if there is not at least one date-value
     * or if the other value is not an integer
     */
    protected function setStartAndEndDate()
    {
      if( is_int($this->_startDate) && is_int($this->_endDate)){
        throw new InvalidConfigException(
          "One of the date values has to be a date value. Integer is given"
        );
      }
      if (is_int($this->_startDate)){
        //end-date and duration always needs a negativ number
        if ($this->_startDate > 0) $this->_startDate = $this->_startDate * -1;
        $this->_startDate = $this->calculateDateWithDuration($this->_endDate, $this->_startDate);
      }

      if (is_int($this->_endDate)){
        $this->_endDate = $this->calculateDateWithDuration($this->_startDate, $this->_endDate);
      }

    }

    /**
     *
     * @return int gap size in unit if startDate is greater than date range start
     */
    protected function getStartGap()
    {
      if ($this->_startDate <= $this->_dateRangeStart) return 0;
      $diffInWeeks = $this->getDiffInWeeks($this->_startDate, $this->_dateRangeStart );
      if ($diffInWeeks === 0) return 1;
      return $this->_startGap = $diffInWeeks * $this->unitSize;
    }


    protected function getProgressLength()
    {
      $start = $this->getProgressStart();
      $end = $this->getProgressEnd();
      $diffInWeeks = $this->getDiffInWeeks($start, $end ) + 1;// +1 because the current week hast to be added

      return $this->_progressLength = $diffInWeeks * $this->unitSize;
    }

    protected function getProgressStart()
    {
      if ( $this->_startDate >= $this->_dateRangeStart ){
        return $this->_startDate;
      } else {
        return $this->_dateRangeStart;
      }
    }

    protected function getProgressEnd()
    {
      if ( $this->_endDate <= $this->_dateRangeEnd ){
        return $this->_endDate;
      } else {
        return $this->_dateRangeEnd;
      }
    }


    protected function getProgressBar()
    {

      $progressBar =  Yii::createObject([
            'class' => 'rottriges\ganttcolumn\GanttProgressBar',
            'startGap' => $this->startGap,
            'length' => $this->progressLength,
            'progressBarType' => $this->_progressType,
            'progressBarColor' => $this->_progressColor,
        ]);
        return $progressBar->getProgressBar();
    }

    protected function checkIfDatesInRange()
    {
      if (
        $this->_startDate >= $this->_dateRangeStart &&
        $this->_endDate <= $this->_dateRangeEnd
      ){
        return true;
      }

      if (
        $this->_startDate <= $this->_dateRangeStart &&
        $this->_endDate >= $this->_dateRangeEnd
      ){
        return true;
      }

      if (
        $this->_startDate <= $this->_dateRangeEnd &&
        $this->_endDate >= $this->_dateRangeStart
      ){
        return true;
      }

      return false;
    }

    protected function getStartAttributeValue($model, $key, $index)
    {
        $attribute = '';
        if (
          !empty($this->ganttOptions['startAttribute']) &&
          $this->ganttOptions['startAttribute'] instanceof Closure
        ) {
            $attribute = call_user_func($this->ganttOptions['startAttribute'], $model, $key, $index, $this);
        } else {
          $attribute = $this->ganttOptions['startAttribute'];

        }
        return $this->getDateAttributeValue($model, $key, $index, $attribute );
    }

    protected function getEndAttributeValue($model, $key, $index)
    {
        $attribute = '';
        if (
          !empty($this->ganttOptions['endAttribute']) &&
          $this->ganttOptions['endAttribute'] instanceof Closure
        ) {
            $attribute = call_user_func($this->ganttOptions['endAttribute'], $model, $key, $index, $this);
        } else {
          $attribute = $this->ganttOptions['endAttribute'];

        }
        return $this->getDateAttributeValue($model, $key, $index, $attribute );
    }

    protected function getDateRange($rangeValue)
    {
        // Check if value is _integer (can be negativ or pos)
        if(is_integer($rangeValue)){
          // + prefix for positiv numebers for additions
          // $val = sprintf("%+d",$rangeValue);
          // return date('Y-m-d', strtotime(date('Y-m-d') . $val .' weeks'));
          return $this->calculateDateWithDuration(date('Y-m-d'), $rangeValue);
        }
        // check if value is correct formated date
        if($this->validateGanttDate($rangeValue)){
          return $rangeValue;
        }
        // if both checks false throw exception
        throw new InvalidConfigException(
          "dateRange must be an integer
          or a date formatedd as ($this->ganttDateFormat)"
        );
    }

    protected function calculateDateWithDuration($date, $duration)
    {
      // + prefix for positiv numebers for additions;
      $val = sprintf("%+d",$duration);
      // The duration must be decimated by a factor of 1, as the current week
      // is already included. For positive values -1 and for negative values +1
      $val = ($val > 0) ? $val - 1 : $val + 1;
      return date('Y-m-d', strtotime($date . $val .' weeks'));
    }

    protected function getDateAttributeValue($model, $key, $index, $attribute)
    {
      if ( $attribute !== null && is_string($attribute) ) {
        $dateAttribute = ArrayHelper::getValue($model, $attribute);

        // check if attribute is date or number (integer) for duration
        if(is_int($dateAttribute)) return $dateAttribute;

        $dateValue = explode(' ', $dateAttribute)[0];
        if ( !$this->validateGanttDate($dateValue) ){
          throw new InvalidConfigException(
            "date format $attribute not correct; right format = $this->ganttDateFormat"
          );
        }
        return $dateValue;
      }
      return null;
    }

    protected function validateGanttDate($dateValue)
    {
        $date = \DateTime::createFromFormat($this->ganttDateFormat, $dateValue);

        if ( $date == false || !(date_format($date,$this->ganttDateFormat) == $dateValue) ){
          return false;
        }
        return true;
    }

    /**
     * column-units for header and content
     *
     * period between dateRangeStart and dateRangeEnd will be devided in single
     * units. (defaul time unit will be weeks)
     * The method determine the total amount of units the amount of units per
     * month and the units per year.
     *
     * @param type var Description
     * @return return units
     */
    protected function getUnits()
    {
        $startDate =  new \DateTime($this->_dateRangeStart);
        $endDate =  new \DateTime($this->_dateRangeEnd);
        // End Range has to be at least 1 second greater than
        // start range to count as a full day
        $endDate->modify('+1 second');
        $interval = 'P1W';

        $period = new \DatePeriod(
          $startDate,
          new \DateInterval($interval),
          $endDate
        );

        $year = 0;
        $month = 0;
        $monthIndex = 0;
        $weekIndex = 0;
        foreach ($period as $key => $value) {
          $y = $value->format('Y');
          $m = $value->format('m');
          $w = $value->format('W');
          if ($year != $y){
            $year = $y;
            $this->_header->years[$y] = 0;
          }
          if ($month != $m){
            $month = $m;
            $monthIndex = $year . '-' . $month;
            $this->_header->months[$monthIndex] = 0;
          }

          $this->_header->years[$year]++;
          $this->_header->months[$monthIndex]++;
          $this->_header->weeks[] = $w;
        }

    }

    /**
     * Compares two dates and returns the diff in weeks
     *
     * gantt progress bar compares weeks irrelevant if one day match the week;
     * due to comparsions within year changes we have to calculate with date->diff;
     * to avoid roundin problems we use always first day of week for calculations;
     *
     * @param type var Description
     * @return return type
     */
    private function getDiffInWeeks($date1, $date2)
    {
        $firstDayofWeek1 = $this->getFirstDayOfWeek($date1);
        $firstDayofWeek2 = $this->getFirstDayOfWeek($date2);
        $d1 =  new \DateTime($firstDayofWeek1);
        $d2 =  new \DateTime($firstDayofWeek2);
        $diffInDays = $d1->diff($d2)->days;
        $diffInWeeks = $diffInDays / 7;

        return floor($diffInWeeks);
    }

    private function getFirstDayOfWeek($date)
    {
      return date("Y-m-d", strtotime('monday this week', strtotime($date)));
    }


}
