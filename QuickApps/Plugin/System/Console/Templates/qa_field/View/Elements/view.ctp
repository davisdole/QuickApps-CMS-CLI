<?php
    switch($display['label']) {
        case 'inline':
            echo "<h4 class=\"field-label\" style=\"display:inline;\">{$field['label']}</h4> ";
        break;

        case 'above':
            echo "<h4 class=\"field-label\">{$field['label']}</h4> ";
        break;
    }

    $field_data = isset($field['FieldData']['data']) ? $field['FieldData']['data'] : '';

    echo $field_data;