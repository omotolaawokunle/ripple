<?php

require_once('helpers.php');
spl_autoload_register(function ($class_name) {

    $directories = array(
        'Model/',
        'Ripple/',
    );
    if (file_exists($class_name . '.php')) {
        require($class_name . '.php');
        return;
    }
    foreach ($directories as $directory) {
        if (file_exists($directory . $class_name . '.php')) {
            require($directory . $class_name . '.php');
            return;
        }
    }
});
