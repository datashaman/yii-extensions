###Description

This widget encapsulates the [Jeditable](http://www.appelsiini.net/projects/jeditable) library in a CInputWidget, so the
widget can be used in CForm definitions and views.

The saveurl parameter expected by Jeditable (the first parameter to the editable call) defaults to $_SERVER['REQUEST_URI'] if not provided in the config of the widget.

Three Jeditable parameters have changed names to avoid collision with CInputWidget attributes:

* id renamed to jeditable_id
* name renamed to jeditable_name
* type renamed to jeditable_type

The jeditable_id parameter which contains the id defaults to 'attribute' so as not to collide with the $_GET parameter, which usually decides which record you're editing.

I've only tested text and textarea types, but assume the rest should work transparently, since the parameters are passed through pretty much verbatim (except for the parameters above).

###Requirements

* Yii 1.0 or above (Yii 1.1 for CForm interaction)

###Installation

* cd yii/project/protected/extensions
* git clone git://github.com/datashaman/yii-extensions.git ds

###Usage

See the following code examples:

In a form definition:

    return array(
      'name' => array(
        'type' => 'application.extensions.ds.jeditable.DsJEditableWidget',
        'jeditable_type' => 'text'
      )
    );

In a view:

    $this->widget('application.extensions.ds.jeditable.DsJEditableWidget', array(
      'jeditable_type' => 'text'
    ))

Further documentation and examples of usage can be found at the [Jeditable home page](http://www.appelsiini.net/projects/jeditable). Remember to use jeditable_id, jeditable_name and jeditable_type wherever it uses id, name or type in the examples.
