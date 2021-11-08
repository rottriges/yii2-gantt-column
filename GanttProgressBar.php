<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\ganttcolumn;

use yii\base\Component;
use yii\bootstrap\Progress;

class GanttProgressBar extends Component
{

    public $startGap = 0;
    public $length = 0;
    public $progressBarType = 'primary';
    public $progressBarColor;
    public $tooltip;

    /**
     * @throws \Exception
     */
    public function getProgressBar(): string
    {
        if ($this->startGap > 0) {
            return $this->getProgressBarWithStartGap();
        }
        return $this->getProgressBarWithoutStartGap();
    }

    /**
     * @throws \Exception
     */
    private function getProgressBarWithoutStartGap(): string
    {
        $progressBarOptions = [
            'class' => 'progress-bar-' . $this->progressBarType,
            'style' => 'width:' . $this->length . 'px; ' . $this->getProgressBarColor(),
        ];

        $progressBarOptions = $this->addTooltip($progressBarOptions);
        return Progress::widget([
            'options' => ['class' => 'ro-progress'],
            'barOptions' => $progressBarOptions
        ]);
    }

    /**
     * @throws \Exception
     */
    private function getProgressBarWithStartGap(): string
    {
        $progressBarOptions = [
            'class' => 'progress-bar-' . $this->progressBarType,
            'style' => 'width:' . $this->length . 'px; ' . $this->getProgressBarColor(),
        ];
        $progressBarOptions = $this->addTooltip($progressBarOptions);
        return Progress::widget([
            'options' => ['class' => 'ro-progress'],
            'bars' => [
                [
                    'percent' => 0,
                    'options' => [
                        'class' => 'progress-bar-empty',
                        'style' => 'width:' . $this->startGap . 'px;'
                    ]
                ],
                [
                    'percent' => 0,
                    'options' => $progressBarOptions
                ]
            ]
        ]);
    }

    private function getProgressBarColor(): string
    {

        if (!$this->progressBarColor) {
            return '';
        }
        return 'background-color:' . $this->progressBarColor . ';';
    }

    private function addTooltip(array $progressBarOptions): array
    {
        if (!$this->tooltip) {
            return $progressBarOptions;
        }
        $progressBarOptions['data-title'] = $this->tooltip;
        $progressBarOptions['data-toggle'] = 'tooltip';
        return $progressBarOptions;
    }
}
