<?php
class DsDateTimeWidget extends CInputWidget
{
  public $dateFormat = 'yyyy-MM-dd';
  public $timeFormat = 'HH:mm:ss';

  public $separator = ' ';

  public $dateOptions = array();
  public $timeOptions = array();

  public function run()
  {
    list($name, $id) = $this->resolveNameID();
    if(!isset($this->htmlOptions['id']))
      $this->htmlOptions['id']=$id;
    if(!isset($this->htmlOptions['name']))
      $this->htmlOptions['name']=$name;

    $this->registerClientScript();

    $value = $this->hasModel() ? $this->model->{$this->attribute} : $this->value;

    $dateTimeFormat = $this->dateFormat.(empty($this->timeFormat) ? '' : $this->separator.$this->timeFormat);

    if(empty($value) || $value == preg_replace('/\w/', '0', $dateTimeFormat)) {
      $date = $this->dateFormat;
      empty($this->timeFormat) or $time = $this->timeFormat;
      $value = null;
    } else {
      $timestamp = CDateTimeParser::parse($value, $dateTimeFormat) or $timestamp = 0;
      $formatter = Yii::app()->getDateFormatter();
      $value = $formatter->format($dateTimeFormat, $timestamp);
      $date = $formatter->format($this->dateFormat, $timestamp);
      empty($this->timeFormat) or $time = $formatter->format($this->timeFormat, $timestamp);
    }

    $divOptions = $this->htmlOptions;
    unset($divOptions['name']);

    echo CHtml::openTag('div', $divOptions);

    if($this->hasModel()) {
      $this->model->{$this->attribute} = $value;
      echo CHtml::activeHiddenField($this->model, $this->attribute);
    } else {
      echo CHtml::hiddenField($this->attribute, $value);
    }

    echo CHtml::textField($this->attribute, $date, array('size' => strlen($this->dateFormat), 'maxlength' => strlen($this->dateFormat)));
    if(!empty($this->timeFormat)) echo CHtml::textField($this->attribute, $time, array('size' => strlen($this->timeFormat), 'maxlength' => strlen($this->timeFormat)));
    echo CHtml::closeTag('div');
  }

  protected function registerClientScript()
  {
    $id = empty($this->htmlOptions['id']) ? $this->id : $this->htmlOptions['id'];

    $dateOptions=$this->dateOptions!==array() ? CJavaScript::encode($this->dateOptions) : '';
    empty($this->timeFormat) or $timeOptions=$this->timeOptions!==array() ? CJavaScript::encode($this->timeOptions) : '';
    $updateHiddenField="function() { var inputs = jQuery(\"#{$id} input\"); inputs.get(0).value = inputs.get(1).value";
    if(empty($timeFormat)) {
      $updateHiddenField.="; }\n";
    } else {
      $updateHiddenField.=" + ".CJavascript::jsonEncode($this->separator)." + inputs.get(2).value; }\n";
    }
    $js="jQuery(\"#{$id} input:eq(1)\").dateEntry({$dateOptions}).change({$updateHiddenField});\n";
    empty($this->timeFormat) or $js.= "jQuery(\"#{$id} input:eq(2)\").timeEntry({$timeOptions}).change({$updateHiddenField});\n";

    $am = Yii::app()->getAssetManager();
    $cs=Yii::app()->getClientScript();

    $cs->registerCoreScript('jquery');

    $cs->registerCssFile($am->publish(dirname(__FILE__).'/assets/css/jquery.dateentry.css'));
    $cs->registerScriptFile($am->publish(dirname(__FILE__).'/assets/js/jquery.dateentry.min.js'));

    if(!empty($this->timeFormat)) {
      $cs->registerCssFile($am->publish(dirname(__FILE__).'/assets/css/jquery.timeentry.css'));
      $cs->registerScriptFile($am->publish(dirname(__FILE__).'/assets/js/jquery.timeentry.min.js'));
    }

    $cs->registerScript('DateTimeWidget#'.$id,$js);
  }

}
