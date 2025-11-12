<?php
// tests/bootstrap.php

// First, load the Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock WordPress filter functions for our tests.
global $mock_filters;
$mock_filters = [];

if (!function_exists('add_filter')) {
    function add_filter($tag, $callable, $priority = 10, $accepted_args = 1) {
        global $mock_filters;
        $mock_filters[$tag][] = $callable;
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters($tag, $value) {
        global $mock_filters;

        if (!isset($mock_filters[$tag])) {
            return $value;
        }

        $args = func_get_args();
        // Remove the tag name from the arguments list.
        array_shift($args);

        foreach ($mock_filters[$tag] as $callable) {
            $value = call_user_func_array($callable, $args);
            // For the next filter, the first argument should be the result of the previous one.
            $args[0] = $value;
        }

        return $value;
    }
}
