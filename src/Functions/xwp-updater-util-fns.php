<?php
/**
 * Updater utility functions and helpers.
 *
 * @package    eXtended WordPress
 * @subpackage Updater
 */

use XWP\Updater\Interfaces\Handles_Updates;
use XWP\Updater\Supervisor;

/**
 * Register an updater for a specific hostname and types
 *
 * @template  T of Handles_Updates
 * @param  callable(): T|class-string<T>|T $updater  Callable which returns an instance of Handles_Updates, updater class name, or an instance of Handles_Updates.
 * @param  string                          $hostname Hostname to register the updater for.
 * @param  string                          ...$types Types to register the updater for. Valid types are `plugin` and `theme`.
 */
function xwp_register_updater(
    callable|string|Handles_Updates $updater,
    string $hostname,
    string ...$types,
): void {
    $callback = match ( true ) {
        is_callable( $updater ) => $updater,
        is_string( $updater )   => static fn() => new $updater(),
        default               => static fn() => $updater,
    };

    Supervisor::instance()->register( $hostname, $types, $callback, );
}
