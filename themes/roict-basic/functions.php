<?php
// ROICT Basic Theme Functions

function do_theme_head(): void {
    // Hook for modules to inject into <head>
    do_action('theme_head');
}

function do_theme_footer(): void {
    // Hook for modules to inject before </body>
    do_action('theme_footer');
}

// Simple action/filter system
$GLOBALS['_cms_actions'] = [];

function add_action(string $hook, callable $callback, int $priority = 10): void {
    $GLOBALS['_cms_actions'][$hook][$priority][] = $callback;
}

function do_action(string $hook, ...$args): void {
    $hooks = $GLOBALS['_cms_actions'][$hook] ?? [];
    ksort($hooks);
    foreach ($hooks as $priority => $callbacks) {
        foreach ($callbacks as $cb) {
            $cb(...$args);
        }
    }
}
