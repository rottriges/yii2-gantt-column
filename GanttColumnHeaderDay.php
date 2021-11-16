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

class GanttColumnHeaderDay extends Component
{
    public $unitSize = 14;
    public $days = [];
    public $hours = [];
    public $minutes = [];

    public $months = [];
    public $weeks = [];

    /**
     * @var array the english month settings.
     */

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
        $interval = 'PT10M';

        $period = new DatePeriod(
            $startDate,
            new DateInterval($interval),
            $endDate
        );
        $day = 0;
        $hour = 0;
        $hourIndex = 0;
        foreach ($period as $value) {
            $d = $value->format('d');
            $h = $value->format('H');
            $minute = $value->format('i');
            if ($day !== $d) {
                $day = $d;
                $this->days[$d] = 0;
            }
            if ($hour !== $h) {
                $hour = $h;
                $hourIndex = $day . '-' . $hour;
                $this->hours[$hourIndex] = 0;
            }

            $this->days[$day]++;
            $this->hours[$hourIndex]++;
            $this->minutes[] = $minute;
        }
    }

    public function getHeaderRow()
    {
        return $this->getDayRow() . $this->getHourRow() . $this->getMinuteRow();
    }

    protected function getDayRow(): string
    {
        $row = '';
        $bgOdd = 'bg-info';
        $bgEven = 'bg-danger';
        $i = 0;
        foreach ($this->days as $day => $units) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            $row .= $this->generateRow($units, $day, $bgColor);
            $i++;
        }
        $row .= Html::tag('div', '', ['class' => 'last-header-col']);
        return $row;
    }

    protected function getHourRow(): string
    {
        $row = '';
        $bgOdd = 'bg-success';
        $bgEven = 'bg-warning';
        $i = 0;

        foreach ($this->hours as $dayHour => $units) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            // explode 'YYYY-mm' to 'mm'
            $value = (int)explode('-', $dayHour)[1];
            $value = GanttColumn::t('ganttColumn', $value) . ':';
            $row .= $this->generateRow($units, $value, $bgColor);
            $i++;
        }
        $row .= Html::tag('div', '', ['class' => 'last-header-col']);
        return $row;
    }

    protected function getMinuteRow(): string
    {
        $row = '';
        $bgOdd = 'minute bg-minute-od';
        $bgEven = 'minute bg-minute-even';
        $i = 0;
        foreach ($this->minutes as $minute) {
            $bgColor = $this->getColBgColor($i, $bgOdd, $bgEven);
            $minuteOutput = $minute[0] . '0';
            $row .= $this->generateRow(1, $minuteOutput, $bgColor);
            $i++;
        }
        $row .= Html::tag('div', '', ['class' => 'last-header-col']);
        return $row;
    }

    private function generateRow($widthVal, $value, $bgColor)
    {
        $width = $this->getColWidth($widthVal);
        $options['class'] = 'header-col ' . $bgColor;
        $options['style'] = 'width: ' . $width;
        return Html::tag('div', $value, $options);
    }


    private function getColWidth($val): string
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
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' 00:00:00');
        if ($duration > 0) {
            $dt->add(new DateInterval('PT' . $duration . 'H'));
        }
        return $dt->format('Y-m-d H:i:s');
    }

    /**
     * @throws \Exception
     */
    public function getDiff(string $date1, string $date2): float
    {
        $d1 = new DateTime($date1);
        $d2 = new DateTime($date2);
        $difSeconds = $d1->getTimestamp() - $d2->getTimestamp();
        $diffInHours = $difSeconds / 60 / 10;

        return floor($diffInHours);
    }
}
