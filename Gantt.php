<?php
/**
 * @package   yii2-gantt
 * @author    Markus Rotter <rottriges@gmail.com>
 * @copyright Copyright &copy; Markus Rotter,  2018 - 2019
 * @version   0.0.2
 */


namespace rottriges\gantt;

use yii\base\Widget;
use yii\helpers\Html;

class Gantt extends Widget
{
    public $message;

    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = 'Ganttchart for Projects';
        }
    }

    public function run()
    {
        return Html::tag('div', Html::encode($this->message),['class'=>'bg-info']);
    }
}
?>
