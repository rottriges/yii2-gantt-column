<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2020
 * @version   0.0.2
 */


namespace rottriges\gantt;

use yii\bootstrap\Progress;
use yii\helpers\Html;

class GanttProgressBar extends \yii\base\Component
{


  public function getProgressBar()
  {
    return Progress::widget([
      'percent' => 65,
      'barOptions' => ['class' => 'progress-bar-danger' ]
    ]);
    // return   '<div class="progress-bar-danger progress-bar" role="progressbar"
    //     aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"
    //     style="width:65%">
    //     <span class="sr-only">65% Complete</span></div>';
  }


}
 ?>
