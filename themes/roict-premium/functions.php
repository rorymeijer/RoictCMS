<?php
// ROICT Basic Theme Functions
// Het hook-systeem (add_action/do_action) wordt geladen via core/bootstrap.php

function do_theme_head(): void {
    do_action('theme_head');
}

function do_theme_footer(): void {
    do_action('theme_footer');
}
