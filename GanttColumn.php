<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\gantt;

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
      public $unitSize = 14;

    public function init()
    {
      parent::init();

      $this->_header = new GanttColumnHeader();
      $this->_header->unitSize = $this->unitSize;

      if (!is_array($this->ganttOptions)) {
        throw new InvalidConfigException("`ganttOptions` is not an array");
      }
      if (!isset($this->ganttOptions['startAttribute']) || !isset($this->ganttOptions['endAttribute'])) {
        throw new InvalidConfigException("`startAttribute` and/or `endAttribute` not defined");
      }
      if (!isset($this->ganttOptions['dateRangeStart']) || !isset($this->ganttOptions['dateRangeStart'])) {
        throw new InvalidConfigException("`dateRangeStart` and/or `dateRangeStart` not defined");
      }

      $this->_dateRangeStart = $this->getDateRange($this->ganttOptions['dateRangeStart']);
      $this->_dateRangeEnd = $this->getDateRange($this->ganttOptions['dateRangeEnd']);

      $this->getUnits();
    }

    public function run()
    {
        $view = $this->getView();
        GanttViewAsset::register($view);
        parent::run();
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
        if (trim($this->width) != '') {
            Html::addCssStyle($options, "width:{$this->width};");
        }
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
        if (!empty($this->ganttOptions) && $this->ganttOptions instanceof Closure) {
            $this->ganttOptions = call_user_func($this->ganttOptions, $model, $key, $index, $this);
        }

        $this->_startDate = $this->getStartAttributeValue($model, $key, $index);
        $this->_endDate = $this->getEndAttributeValue($model, $key, $index);

        if ($this->_startDate !== null && $this->_endDate !== null) {
          return 'Test';
        }

        return $this->grid->emptyCell;
    }


    protected function getProgressBar()
    {
      $progressBar = '
        <div class="progress">
          <div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;">
            60%
          </div>
        </div>';
        return $progressBar;
    }

    protected function getStartAttributeValue($model, $key, $index)
    {
        $attribute = $this->ganttOptions['startAttribute'];
        return $this->getDateAttributeValue($model, $key, $index, $attribute );
    }

    protected function getEndAttributeValue($model, $key, $index)
    {
        $attribute = $this->ganttOptions['endAttribute'];
        return $this->getDateAttributeValue($model, $key, $index, $attribute );
    }

    protected function getDateRange($rangeValue)
    {
        // Check if value is _integer (can be negativ or pos)
        if(is_integer($rangeValue)){
          // + prefix for positiv numebers for additions
          $val = sprintf("%+d",$rangeValue);
          return date('Y-m-d', strtotime(date('Y-m-d') . $val .' weeks'));
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

    protected function getDateAttributeValue($model, $key, $index, $attribute)
    {
      if ( $attribute !== null && is_string($attribute) ) {
        $dateAttribute = ArrayHelper::getValue($model, $attribute);
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




}
