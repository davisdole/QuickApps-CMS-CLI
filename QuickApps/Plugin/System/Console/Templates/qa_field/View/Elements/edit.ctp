<?php
    $actual_value = isset($field['FieldData']['data']) ? $field['FieldData']['data'] : '';

    // Storage ID
    echo $this->Form->hidden("FieldData.FieldName.{$field['id']}.id", array('value' => $field['FieldData']['id']));

    // Storage DATA
    echo $this->Form->input("FieldData.FieldName.{$field['id']}.data", array('label' => $field['label'], 'value' => $actual_value));
?>

<?php
    // Field help
    if (!empty($field['description'])):
?>
    <em><?php echo $field['description']; ?></em>
<?php endif; ?>