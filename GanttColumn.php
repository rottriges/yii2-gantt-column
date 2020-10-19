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
  public $header = 'GanttCell';

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
       * @var string the date format for endDate for ther processbar
       */
      public $gantDateFormat = 'Y-m-d';



  /**
     * Renders the header cell content.
     * The default implementation simply renders [[header]].
     * This method may be overridden to customize the rendering of the header cell.
     * @return string the rendering result
     */
    protected function renderHeaderCellContent()
    {
        // return trim($this->header) !== '' ? $this->header : $this->getHeaderCellLabel();
        return '<div class="bg-danger">Year</div><div class="bg-info">Month</div>';
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
        if (!is_array($this->ganttOptions)) {
          throw new InvalidConfigException("`ganttOptions` is not an array");
        }
        if (!isset($this->ganttOptions['startAttribute']) || !isset($this->ganttOptions['endAttribute'])) {
          throw new InvalidConfigException("`startAttribute` and `endAttribute` not defined");
        }

        // TODO: function getDateAttribute and validate date format
        $this->_startDate = $this->ganttOptions['startAttribute'];
        $this->_endDate = $this->ganttOptions['startAttribute'];

        foreach ([$this->_startDate, $this->_endDate] as  $dateParam) {
          $date = \DateTime::createFromFormat($this->gantDateFormat, $dateParam);
          if ( $date == false || !(date_format($date,$this->gantDateFormat) == $dateParam) ){
            throw new InvalidConfigException("wrong format for $dateParam");
          }
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




}
