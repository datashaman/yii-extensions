##Basic Instructions
Setup the controller in your config/main.php like so

    ...
    'import' => array(
      'application.extensions.ds.crudify.*',
    ),
    ...
    'controllerMap' => array(
      'crud' => array(
        'class' => 'CrudController',
        'pageSize' => 10,
      ),
    )
    ...

Link the crud views into your base view path:

    cd protected/views
    ln -s ../extensions/ds/crudify/views/ crud

Navigate to http://yii/app/index.php?r=crud/admin&model=User or whatever model you want to administer. You should see an admin grid. YMMV.

There are a few conventions I've adopted, which make crudify work for me: models must have an id attribute, and a name attribute (or getName() method).

If you want an attribute to be automatically treated as a date or datetime, put a validator in the rules collection, of type 'type' and parameter type is 'date'. If the dateFormat parameter on the rule is longer than 10 characters, it is assumed to be datetime, otherwise simple date.

If an attribute has name 'password', it becomes a password element.

If an attribute ends in '_at' it becomes a DsDateTimeWidget element with date and time parts.

If an attribute ends in '_on' it becomes a DsDateTimeWidget element with only a date part.

By default, fields named created_by_id, created_at, updated_by_id, updated_at, deleted_by_id and deleted_at are not displayed when adding a new record. They are displayed when editing an existing record (but not editable).

Relationships between models are automatically taken care of (mostly), by reading the metadata and presenting the appropriate content.

If you want to override an automatically generated form, create a CForm config file in application.forms with the same name as the model. For example, for a user model, create a file protected/forms/User.php with your CForm config. If this does not exist, the form config is generated from the model.

If you want to override the automatically generated view, create a view file in the appropriate place for a controller of the same name as the model. So for example, for a user model, create a file protected/views/user/view.php. If this does not exist, the view is generated from the model.

Note the crudify extension uses the DsDateTimeWidget and expects to be hosted at application.extensions.ds.crudify. 

###Credits
[Silk icon set 1.3] (http://www.famfamfam.com/lab/icons/silk/)
Mark James
