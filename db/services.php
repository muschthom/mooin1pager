<?php
$functions = [
    'format_mooin1pager_execute' => [
        'classname'   => 'format_mooin1pager\\format_mooin1pager_external',
        'methodname'  => 'execute',
        'classpath' => 'course/format/mooin1pager/externallib.php',
        'description' => 'Check completion status',
        'type'        => 'read',
        'ajax'        => true,
    ],
];


$services = array(
    'format_mooin1pager_execute' => array(
        'functions' => array('format_mooin1pager_execute'),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);