<?php
class DsJEditableWidget extends CInputWidget
{
  // The URL the editable content is saved to
  public $saveurl = null;

  // Method to use when submitting edited content.
  public $method = 'POST';

  // Function is called after form has been submitted.
  // Callback function receives two parameters.
  // Value contains submitted form content.
  // Settings contain all plugin settings.
  // Inside function this refers to the original element.
  public $callback = null;

  // Name of the submitted parameter which contains edited content.
  public $jeditable_name = 'value';

  // Name of the submitted parameter which contains id.
  public $jeditable_id = 'attribute';

  // Extra parameters when submitting content.
  // Can be either a hash or function returning a hash.
  public $submitdata = null;

  // Input type to use. Default input types are text, textarea or select.
  public $jeditable_type = 'text';

  // Number of rows if using textarea.
  public $rows = null;

  // Number of columns if using textarea.
  public $cols = null;

  // Height of the input element in pixels.
  // Can also be set to none.
  public $height = 'auto';

  // Width of the input element in pixels.
  // Can also be set to none.
  public $width = 'auto';

  // Load content of the element from an external URL.
  public $loadurl = null;

  // Request type to use when using loadurl.
  public $loadtype = 'GET';

  // Extra parameters to add to request when using loadurl.
  public $loaddata = null;

  // Form data passed as parameter. Can be either a string or function returning a string.
  public $data = null;

  public function run()
  {
    list($name, $id) = $this->resolveNameID();
		if(!isset($this->htmlOptions['id']))
			$this->htmlOptions['id']=$id;
		if(!isset($this->htmlOptions['name']))
			$this->htmlOptions['name']=$name;

		$this->registerClientScript();

    if($this->hasModel()) {
      echo CHtml::tag('div', $this->htmlOptions, CHtml::encode($this->model->{$this->attribute}));
    } else {
      echo CHtml::tag('div', $this->htmlOptions, CHtml::encode($this->value));
    }
  }

  protected function registerClientScript()
  {
    $id = empty($this->htmlOptions['id']) ? $this->id : $this->htmlOptions['id'];
    $saveurl = empty($this->saveurl) ? $_SERVER['REQUEST_URI'] : $this->saveurl;
		$miOptions=$this->getClientOptions();
		$options=$miOptions!==array() ? ','.CJavaScript::encode($miOptions) : '';
		$js="jQuery(\"#{$id}\").editable(\"{$saveurl}\"{$options});";

		$cs=Yii::app()->getClientScript();
		$cs->registerCoreScript('jquery');
		$cs->registerScriptFile(Yii::app()->getAssetManager()->publish(dirname(__FILE__).'/jquery.jeditable.mini.js'));
		$cs->registerScript('EditableInputElement#'.$id,$js);
  }

 	/**
	 * @return array the options for the text field
	 */
	protected function getClientOptions()
	{
		$options=array();

    foreach(array('method', 'submitdata', 'rows', 'cols', 'height', 'width', 'loadurl', 'loadtype', 'loaddata', 'data') as $property) {
      $this->$property === null or $options[$property]=$this->$property;
    }

    $options['id'] = $this->jeditable_id;
    $options['name'] = $this->jeditable_name;
    $options['type'] = $this->jeditable_type;

		if(is_string($this->callback))
		{
			if(strncmp($this->callback,'js:',3))
				$options['callback']='js:'.$this->callback;
			else
				$options['callback']=$this->callback;
		}

		return $options;
	}
}
