<?php declare(strict_types=1);

namespace Parable\Framework;

class EventTriggers
{
    /**
     * Application triggers
     */
    public const APPLICATION_BOOT_BEFORE = 'parable_boot_before';
    public const APPLICATION_BOOT_AFTER = 'parable_boot_after';
    public const APPLICATION_RUN_BEFORE = 'parable_run_before';
    public const APPLICATION_RUN_AFTER = 'parable_run_after';
    public const APPLICATION_ROUTE_MATCH_FOUND = 'parable_route_match_found';
    public const APPLICATION_ROUTE_MATCH_NOT_FOUND = 'parable_route_match_not_found';
    public const APPLICATION_INIT_DATABASE_BEFORE = 'parable_init_database_before';
    public const APPLICATION_INIT_DATABASE_AFTER = 'parable_init_database_after';
    public const APPLICATION_SET_DEFAULT_TIMEZONE_BEFORE = 'parable_set_default_timezone_before';
    public const APPLICATION_SET_DEFAULT_TIMEZONE_AFTER = 'parable_set_default_timezone_after';
    public const APPLICATION_RESPONSE_DISPATCH_BEFORE = 'parable_response_dispatch_before';
    public const APPLICATION_RESPONSE_DISPATCH_AFTER = 'parable_response_dispatch_after';
    public const APPLICATION_ROUTE_MATCH_BEFORE = 'parable_route_match_before';
    public const APPLICATION_ROUTE_MATCH_AFTER = 'parable_route_match_after';
    public const APPLICATION_SESSION_START_BEFORE = 'parable_session_start_before';
    public const APPLICATION_SESSION_START_AFTER = 'parable_session_start_after';
    public const APPLICATION_PLUGINS_START_BEFORE_BOOT_BEFORE = 'parable_plugins_start_before_boot_before';
    public const APPLICATION_PLUGINS_START_BEFORE_BOOT_AFTER = 'parable_plugins_start_before_boot_after';
    public const APPLICATION_PLUGINS_START_AFTER_BOOT_BEFORE = 'parable_plugins_start_after_boot_before';
    public const APPLICATION_PLUGINS_START_AFTER_BOOT_AFTER = 'parable_plugins_start_after_boot_after';

    /**
     * Route dispatcher triggers
     */
    public const ROUTE_DISPATCHER_DISPATCH_BEFORE = 'parable_route_dispatch_before';
    public const ROUTE_DISPATCHER_DISPATCH_AFTER = 'parable_route_dispatch_after';
    public const ROUTE_DISPATCHER_DISPATCH_TEMPLATE_BEFORE = 'parable_route_dispatch_template_before';
    public const ROUTE_DISPATCHER_DISPATCH_TEMPLATE_AFTER = 'parable_route_dispatch_template_after';
}
