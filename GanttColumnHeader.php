<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\ganttcolumn;

use DateInterval;
use DatePeriod;
use DateTime;
use yii\base\Component;
use yii\helpers\Html;

class GanttColumnHeader extends Component
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
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ],
        'monthsShort' => [
            1 => 'Jan',
            2 => 'Feb',
            3 => 'Mar',
            4 => 'Apr',
            5 => 'May',
            6 => 'Jun',
            7 => 'Jul',
            8 => 'Aug',
            9 => 'Sep',
            10 => 'Oct',
            11 => 'Nov',
            12 => 'Dec',
        ],
    ];

    /**
     * column-units for header and content
     *
     * period between dateRangeStart and dateRangeEnd will be devided in single
     * units. (defaul time unit will be weeks)
     * The method determine the total amount of units the amount of units per
     * month and the units per year.
     *
     * @param string $dateRangeStart
     * @param string $dateRangeEnd
     * @return void
     * @throws \Exception
     */
    public function getUnits(string $dateRangeStart, string  $dateRangeEnd): void
    {
        $startDate = new DateTime($dateRangeStart);
        $endDate = new DateTime($dateRangeEnd);
        // End Range has to be at least 1 second greater than
        // start range to count as a full day
        $endDate->modify('+1 second');
        $interval = 'P1W';

        $period = new DatePeriod(
            $startDate,
            new DateInterval($interval),
            $endDate
        );

        $year = 0;
        $month = 0;
        $monthIndex = 0;
        foreach ($period as $value) {
            $y = $value->format('Y');
            $m = $value->format('m');
            $w = $value->format('W');
            if ($year !== $y) {
                $year = $y;
                $this->years[$y] = 0;
            }
            if ($month !== $m) {
                $month = $m;
                $monthIndex = $year . '-' . $month;
                $this->months[$monthIndex] = 0;
            }

            $this->years[$year]++;
            $this->months[$monthIndex]++;
            $this->weeks[] = $w;
        }
    }

    public function getHeaderRow(): string
    {
        return $this->getYearRow() . $this->getMonthRow() . $this->getWeekRow();
    }

    protected function getYearRow(): string
    {
        $row = '';
        $bgOdd = 'bg-info';
        $bgEven = 'bg-danger';
        $i = 0;
        foreach ($this->years as $year => $units) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            $row .= $this->generateRow($units, $year, $bgColor);
            $i++;
        }
        $row .= Html::tag('div', '', ['class' => 'last-header-col']);
        return $row;
    }

    protected function getMonthRow(): string
    {
        $row = '';
        $bgOdd = 'bg-success';
        $bgEven = 'bg-warning';
        $i = 0;
        // GanttColumnHeader::registerTranslations();
        foreach ($this->months as $yearMonth => $units) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            // explode 'YYYY-mm' to 'mm'
            $value = (int)explode('-', $yearMonth)[1];
            $value = self::$_months['monthsShort'][$value];
            $value = GanttColumn::t('ganttColumn', $value);
            $row .= $this->generateRow($units, $value, $bgColor);
            $i++;
        }
        $row .= Html::tag('div', '', ['class' => 'last-header-col']);
        return $row;
    }

    protected function getWeekRow(): string
    {
        $row = '';
        $bgOdd = 'week bg-week-od';
        $bgEven = 'week bg-week-even';
        $i = 0;
        foreach ($this->weeks as $week) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            $row .= $this->generateRow(1, $week, $bgColor);
            $i++;
        }
        return  Html::tag('div', $row, ['class' => 'last-header-col']);
    }

    private function generateRow($widthVal, $value, $bgColor): string
    {
        $width = $this->getColWidth($widthVal);
        $options['class'] = 'header-col ' . $bgColor;
        $options['style'] = 'width: ' . $width;
        return Html::tag('div', $value, $options);
    }


    private function getColWidth($val)
    {
        $width = $val * $this->unitSize;
        return $width . 'px';
    }

    private function getColBgColor($val, $bgEven, $bgOdd)
    {
        // even $val
        if ($val % 2) {
            return $bgEven;
        }

        // odd $val
        return $bgOdd;
    }

    public function calculateDateWithDuration($date, $duration)
    {
        // + prefix for positiv numebers for additions;
        $val = sprintf('%+d', $duration);
        // The duration must be decimated by a factor of 1, as the current week
        // is already included. For positive values -1 and for negative values +1
        $val = ($val > 0) ? $val -- : $val ++;
        return date('Y-m-d', strtotime($date . $val . ' week'));
    }

    public function getDiff(string $date1, string $date2): float
    {
        $firstDayofWeek1 = self::getFirstDayOfWeek($date1);
        $firstDayofWeek2 = self::getFirstDayOfWeek($date2);
        $d1 = new DateTime($firstDayofWeek1);
        $d2 = new DateTime($firstDayofWeek2);
        $diffInDays = $d1->diff($d2)->days;
        $diffInWeeks = $diffInDays / 7;

        return floor($diffInWeeks);
    }

    private static function getFirstDayOfWeek($date)
    {
        return date('Y-m-d', strtotime('monday this week', strtotime($date)));
    }
}
