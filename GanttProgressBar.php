<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\ganttcolumn;

use yii\bootstrap\Progress;
use yii\helpers\Html;

class GanttProgressBar extends \yii\base\Component
{

  public $startGap = 0;
  public $length = 0;
  public $progressBarType = 'primary';

  public function getProgressBar()
  {
    if ($this->startGap > 0){
      return $this->getProgressBarWithStartGap();
    }
    return $this->getProgressBarWithoutStartGap();
  }

  protected function getProgressBarWithoutStartGap()
  {
    return Progress::widget([
        'options' => ['class' => 'ro-progress' ],
        'barOptions' => [
          'class' => 'progress-bar-' . $this->progressBarType,
          'style' => 'width:' . $this->length . 'px;'
        ]
    ]);
  }

  protected function getProgressBarWithStartGap()
  {
    return Progress::widget([
      'options' => ['class' => 'ro-progress' ],
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
            'options' => [
              'class' => 'progress-bar-' . $this->progressBarType,
              'style' => 'width:' . $this->length . 'px;'
            ]
          ]
      ]
    ]);
  }





}
 ?>
