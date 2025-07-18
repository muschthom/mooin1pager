<?php
$functions = array(
    'format_mooin1pager_execute' => array(
        'classname'   => 'format_mooin1pager\\format_mooin1pager_external',
        'methodname'  => 'execute',
        'classpath' => 'course/format/mooin1pager/externallib.php',
        'description' => 'Check completion status',
        'type'        => 'read',
        'ajax'        => true,
    ),

    'format_mooin1pager_setgrade' => array(
        'classname'   => 'format_mooin1pager\\format_mooin1pager_external',
        'methodname' => 'setgrade',
        'classpath' => 'course/format/mooin1pager/externallib.php',
        'description' => 'Set H5P grade',
        'type' => 'write',
        'ajax' => true
    ),

);


$services = array(
    'mooin1pager_execute' => array(
        'functions' => array('format_mooin1pager_execute', 'format_mooin1pager_setgrade'),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
