###Description

This widget encapsulates the JEditable library in a CInputWidget, so the
widget can be used in CForm definitions.

The saveurl parameter expected by JEditable defaults to $_SERVER['REQUEST_URI'] if not provided in the config of the widget.

Three JEditable parameters have changed names to avoid collision with CInputWidget attributes:

* id renamed to jeditable_id
* name renamed to jeditable_name
* type renamed to jeditable_type

The jeditable_id parameter which contains the id defaults to 'attribute' so as not to collide with the $_GET parameter, which usually decides which record you're editing.

I've only tested text and textarea types, but assume the rest should work transparently, since the parameters are passed through pretty much verbatim (except for the parameters above).

###Requirements

* Yii 1.0 or above

###Installation

* Extract the release file under `protected/extensions`

###Usage

See the following code examples:

In a form definition:

    return array(
      'name' => array(
        'type' => 'application.extensions.jeditable.DsJEditableWidget',
        'jeditable_type' => 'text'
      )
    );

In a view:

    $this->widget('application.extensions.jeditable.DsJEditableWidget', array(
      'jeditable_type' => 'text'
    ))
