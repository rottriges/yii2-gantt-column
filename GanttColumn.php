<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\ganttcolumn;

use Closure;
use DateTime;
use Yii;
use yii\base\InvalidConfigException;
use yii\grid\DataColumn;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\i18n\PhpMessageSource;

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
     * of the signature: `function ($model, $key, $index)`, where:
     * - `$model`: _\yii\base\Model_, is the data model.
     * - `$key`: _string|object_, is the primary key value associated with the data model.
     * - `$index`: _integer_, is the zero-based index of the data model among the model array returned by [[dataProvider]].
     *
     *
     * This property is for the additional settings for gantt column options
     */
    public $ganttOptions = [];

    /*
    * header class
    *
    * default  = year - month - week
    *
    * could be changed to day - hour - minute
    * 'columnHeader' => \rottrigs\ganttcolumn\GanttColumnHeaderDay::class,
    * ...
     */
    public $columnHeader = GanttColumnHeader::class;

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
     * progress typ (primary, danger, success, warnin or info)
     */
    private $_progressType;

    /**
     * progress color string|closure
     */
    private $_progressColor;

    /**
     * @var string
     */
    private $_tooltip;


    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init(): void
    {
        parent::init();

        self::registerTranslations();
        $this->_header = new $this->columnHeader;
        $this->_header->unitSize = $this->unitSize;
        self::registerTranslations();

        if (!is_array($this->ganttOptions)) {
            throw new InvalidConfigException('`ganttOptions` is not an array');
        }

        if (!isset($this->ganttOptions['startAttribute'], $this->ganttOptions['endAttribute'])) {
            throw new InvalidConfigException('`startAttribute` and/or `endAttribute` not defined');
        }

        if (!isset($this->ganttOptions['dateRangeStart'], $this->ganttOptions['dateRangeEnd'])) {
            throw new InvalidConfigException('`dateRangeStart` and/or `dateRangeStart` not defined');
        }

        if (!isset($this->ganttOptions['progressBarType'])) {
            $this->ganttOptions['progressBarType'] = 'primary';
        }

        if (!isset($this->ganttOptions['progressBarColor'])) {
            $this->ganttOptions['progressBarColor'] = '';
        }

        if (!isset($this->ganttOptions['tooltip'])) {
            $this->ganttOptions['tooltip'] = '';
        }

        $this->_dateRangeStart = $this->getDateRange($this->ganttOptions['dateRangeStart']);
        $this->_dateRangeEnd = $this->getDateRange($this->ganttOptions['dateRangeEnd']);

        $this->_header->getUnits($this->_dateRangeStart, $this->_dateRangeEnd);
        $this->unitSum = count($this->_header->weeks);

        $view = Yii::$app->getView();
        GanttViewAsset::register($view);
    }

    public static function registerTranslations(): void
    {
        $i18n = Yii::$app->i18n;
        $i18n->translations['ganttColumn'] = [
            'class' => PhpMessageSource::class,
            'sourceLanguage' => 'en-US',
            'basePath' => __DIR__ . '/messages',
        ];
    }

    public static function t($category, $message, $params = [], $language = null): string
    {
        return Yii::t($category, $message, $params, $language);
    }


    /**
     * Renders the header cell content.
     * The default implementation simply renders [[header]].
     * This method may be overridden to customize the rendering of the header cell.
     * @return string the rendering result
     */
    protected function renderHeaderCellContent(): string
    {
        return $this->_header->headerRow;
    }

    /**
     * Renders a data cell.
     * @param mixed $model the data model being rendered
     * @param mixed $key the key associated with the data model
     * @param int $index the zero-based index of the data item among the item array returned by [[GridView::dataProvider]].
     * @return string the rendering result
     * @throws \yii\base\InvalidConfigException
     */
    public function renderDataCell($model, $key, $index): string
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
     * @throws \yii\base\InvalidConfigException
     */
    protected function renderDataCellContent($model, $key, $index): string
    {

        $this->_startDate = $this->getStartAttributeValue($model, $key, $index);
        $this->_endDate = $this->getEndAttributeValue($model, $key, $index);
        $this->setStartAndEndDate();

        if (!empty($this->ganttOptions['progressBarType']) &&
            $this->ganttOptions['progressBarType'] instanceof Closure
        ) {
            $this->_progressType = call_user_func($this->ganttOptions['progressBarType'], $model, $key, $index, $this);
        } else {
            $this->_progressType = $this->ganttOptions['progressBarType'];
        }

        if (!empty($this->ganttOptions['progressBarColor']) &&
            $this->ganttOptions['progressBarColor'] instanceof Closure
        ) {
            $this->_progressColor = call_user_func($this->ganttOptions['progressBarColor'], $model, $key, $index, $this);
        } else {
            $this->_progressColor = $this->ganttOptions['progressBarColor'];
        }

        if (!empty($this->ganttOptions['tooltip']) &&
            $this->ganttOptions['tooltip'] instanceof Closure
        ) {
            $this->_tooltip = call_user_func($this->ganttOptions['tooltip'], $model, $key, $index, $this);
        } else {
            $this->_tooltip = $this->ganttOptions['tooltip'];
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
     * @return void
     * @throws InvalidConfigException if there is not at least one date-value
     * or if the other value is not an integer
     */
    protected function setStartAndEndDate(): void
    {
        if (is_int($this->_startDate) && is_int($this->_endDate)) {
            throw new InvalidConfigException(
                'One of the date values has to be a date value. Integer is given'
            );
        }
        if (is_int($this->_startDate)) {
            //end-date and duration always needs a negativ number
            if ($this->_startDate > 0) {
                $this->_startDate *= -1;
            }
            $this->_startDate = $this->_header->calculateDateWithDuration($this->_endDate, $this->_startDate);
        }

        if (is_int($this->_endDate)) {
            $this->_endDate = $this->_header->calculateDateWithDuration($this->_startDate, $this->_endDate);
        }
    }

    /**
     *
     * @return int gap size in unit if startDate is greater than date range start
     */
    protected function getStartGap()
    {
        if ($this->_startDate <= $this->_dateRangeStart) {
            return 0;
        }
        $diff = $this->_header->getDiff($this->_startDate, $this->_dateRangeStart);
        if ($diff === 0) {
            return 1;
        }
        return $_startGap = $diff * $this->unitSize;
    }


    protected function getProgressLength()
    {
        $start = $this->getProgressStart();
        $end = $this->getProgressEnd();
        $diff = $this->_header->getDiff($end, $start) + 1;// +1 because the current week hast to be added

        return $_progressLength = $diff * $this->unitSize;
    }

    protected function getProgressStart(): string
    {
        if ($this->_startDate >= $this->_dateRangeStart) {
            return $this->_startDate;
        }
        return $this->_dateRangeStart;
    }

    protected function getProgressEnd(): string
    {
        if ($this->_endDate <= $this->_dateRangeEnd) {
            return $this->_endDate;
        }
        return $this->_dateRangeEnd;
    }


    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \Exception
     */
    protected function getProgressBar(): string
    {

        $progressBar = Yii::createObject([
            'class' => GanttProgressBar::class,
            'startGap' => $this->startGap,
            'length' => $this->progressLength,
            'progressBarType' => $this->_progressType,
            'progressBarColor' => $this->_progressColor,
            'tooltip' => $this->_tooltip
        ]);
        return $progressBar->getProgressBar();
    }

    protected function checkIfDatesInRange(): bool
    {
        if ($this->_startDate >= $this->_dateRangeStart &&
            $this->_endDate <= $this->_dateRangeEnd
        ) {
            return true;
        }

        if ($this->_startDate <= $this->_dateRangeStart &&
            $this->_endDate >= $this->_dateRangeEnd
        ) {
            return true;
        }

        if ($this->_startDate <= $this->_dateRangeEnd &&
            $this->_endDate >= $this->_dateRangeStart
        ) {
            return true;
        }

        return false;
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function getStartAttributeValue($model, $key, $index)
    {
        if (!empty($this->ganttOptions['startAttribute']) &&
            $this->ganttOptions['startAttribute'] instanceof Closure
        ) {
            $attribute = call_user_func($this->ganttOptions['startAttribute'], $model, $key, $index, $this);
        } else {
            $attribute = $this->ganttOptions['startAttribute'];
        }
        return $this->getDateAttributeValue($model, $key, $index, $attribute);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function getEndAttributeValue($model, $key, $index)
    {
        if (!empty($this->ganttOptions['endAttribute']) &&
            $this->ganttOptions['endAttribute'] instanceof Closure
        ) {
            $attribute = call_user_func($this->ganttOptions['endAttribute'], $model, $key, $index, $this);
        } else {
            $attribute = $this->ganttOptions['endAttribute'];
        }
        return $this->getDateAttributeValue($model, $key, $index, $attribute);
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function getDateRange($rangeValue)
    {
        // Check if value is _integer (can be negativ or pos)
        if (is_int($rangeValue)) {
            // + prefix for positiv numebers for additions
            // $val = sprintf("%+d",$rangeValue);
            // return date('Y-m-d', strtotime(date('Y-m-d') . $val .' weeks'));
            return $this->_header->calculateDateWithDuration(date('Y-m-d'), $rangeValue);
        }
        // check if value is correct formated date
        if ($this->validateGanttDate($rangeValue)) {
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
        $val = sprintf('%+d', $duration);
        // The duration must be decimated by a factor of 1, as the current week
        // is already included. For positive values -1 and for negative values +1
        $val = ($val > 0) ? $val -- : $val ++;
        return date('Y-m-d', strtotime($date . $val . ' ' . $this->_header->getDurationStep()));
    }

    /**
     * @throws \yii\base\InvalidConfigException
     */
    protected function getDateAttributeValue($model, $key, $index, $attribute)
    {
        if (is_string($attribute)) {
            $dateAttribute = ArrayHelper::getValue($model, $attribute);

            // check if attribute is date or number (integer) for duration
            if (is_int($dateAttribute)) {
                return $dateAttribute;
            }

            if ($this->ganttDateFormat === 'Y-m-d') {
                $dateValue = explode(' ', $dateAttribute)[0];
                if (!$this->validateGanttDate($dateValue)) {
                    throw new InvalidConfigException(
                        "date format $attribute not correct; right format = $this->ganttDateFormat"
                    );
                }
                return $dateValue;
            }
            if (!$dt = DateTime::createFromFormat($this->ganttDateFormat, $dateAttribute)) {
                throw new InvalidConfigException(
                    "date format $attribute not correct; right format = $this->ganttDateFormat"
                );
            }

            return $dateAttribute;

        }
        return null;
    }

    protected function validateGanttDate($dateValue): bool
    {
        $date = DateTime::createFromFormat($this->ganttDateFormat, $dateValue);
        return !($date == false || !(date_format($date, $this->ganttDateFormat) === $dateValue));
    }

    /**
     * Compares two dates and returns the diff in weeks
     *
     * gantt progress bar compares weeks irrelevant if one day match the week;
     * due to comparsions within year changes we have to calculate with date->diff;
     * to avoid roundin problems we use always first day of week for calculations;
     *
     * @param string $date1
     * @param string $date2
     * @return float type
     * @throws \Exception
     */
    private function getDiffInWeeks(string $date1, string $date2): float
    {
        $firstDayofWeek1 = $this->getFirstDayOfWeek($date1);
        $firstDayofWeek2 = $this->getFirstDayOfWeek($date2);
        $d1 = new DateTime($firstDayofWeek1);
        $d2 = new DateTime($firstDayofWeek2);
        $diffInDays = $d1->diff($d2)->days;
        $diffInWeeks = $diffInDays / 7;

        return floor($diffInWeeks);
    }

    private function getFirstDayOfWeek($date)
    {
        return date('Y-m-d', strtotime('monday this week', strtotime($date)));
    }
}
