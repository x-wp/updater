<?php // phpcs:disable SlevomatCodingStandard.Namespaces.FullyQualifiedExceptions.NonFullyQualifiedException
/**
 * Supervisor class file.
 *
 * @package    eXtended WordPress
 * @subpackage Updater
 */

namespace XWP\Updater;

use InvalidArgumentException as IArgEx;
use XWP\Helper\Traits\Singleton;
use XWP\Updater\Interfaces\Handles_Updates;

/**
 * Supervises the update package handlers and the update process.
 */
class Supervisor {
    use Singleton;

    /**
     * Valid info API calls.
     *
     * @var array<string>
     */
    private array $api = array( 'plugin_information', 'theme_information' );

    /**
     * Update Handler callables.
     *
     * @var array<string,callable(): Handles_Updates>
     */
    private array $handler_cbs = array();

    /**
     * Array of registered updaters.
     *
     * @var array<string,Handles_Updates>
     */
    private array $handlers = array();

    /**
     * Array of hostnames with last registered priority.
     *
     * @var array<string,int>
     */
    private array $hostnames = array();

    /**
     * Dynamically handles method calls for update and API callbacks.
     *
     * @param  string       $name Method name.
     * @param  array<mixed> $args Arguments passed to the method.
     * @return mixed
     *
     * @throws IArgEx If the method name is invalid.
     */
    public function __call( string $name, array $args ): mixed {
        \preg_match( '/^(update|api)_(.*)$/', $name, $matches );

        try {
            $args[] = $this->get_handler( $matches[2] ?? '' );

            return match ( $matches[1] ?? '' ) {
                'update' => $this->update_callback( ...$args ),
                'api'    => $this->api_callback( ...$args ),
                default  => throw new IArgEx( 'Invalid method name' ),
            };
        } catch ( IArgEx $e ) {
            \_doing_it_wrong( __METHOD__, \esc_html( $e->getMessage() ), '1.0.0' );

            return false;
        }
    }

    /**
     * Register an updater for a specific hostname and types.
     *
     * @param  string                         $hostname Hostname to register the updater for.
     * @param  array{0:'plugin', 1?:'themes'} $types    Types to register the updater for. Valid types are `plugin` and `theme`.
     * @param  callable(): Handles_Updates    $callback Callable which returns an update handler instance.
     */
    public function register( string $hostname, array $types, callable $callback ): void {
        $hostname = \wp_parse_url( \sanitize_url( $hostname ), PHP_URL_HOST );
        $callback = $this->add_handler( $callback );
        $priority = $this->add_hostname( $hostname );

        foreach ( $types as $type ) {
            $type = \rtrim( $type, 's' ) . 's';

            \add_filter( "update_{$type}_{$hostname}", array( $this, "update_{$callback}" ), $priority, 4 );
            \add_filter( "{$type}_api", array( $this, "api_{$callback}" ), $priority, 3 );
        }
    }

    /**
     * Add an updater callable to the list of updaters.
     *
     * @param  callable(): Handles_Updates $updater Updater callable.
     * @return string
     */
    private function add_handler( callable $updater ): string {
        $callback = \str_replace( '-', '', \wp_generate_uuid4() );

        $this->handler_cbs[ $callback ] = $updater;

        return $callback;
    }

    /**
     * Get the handler for a specific ID.
     *
     * @param  string $id Handler ID.
     * @return Handles_Updates
     *
     * @throws IArgEx If the handler ID is invalid.
     */
    private function get_handler( string $id ): Handles_Updates {
        if ( isset( $this->handlers[ $id ] ) ) {
            return $this->handlers[ $id ];
        }

        $cb = $this->handler_cbs[ $id ] ?? throw new IArgEx( 'Invalid handler ID' );

        return $this->handlers[ $id ] ??= $cb();
    }

    /**
     * Add a hostname to the list of hostnames.
     *
     * @param  string $hostname Hostname.
     * @return int
     */
    private function add_hostname( string $hostname ): int {
        $this->hostnames[ $hostname ] ??= -1;

        return ++$this->hostnames[ $hostname ];
    }

    /**
     * Run the update callback.
     *
     * @param  false|array<string,mixed> $update_data  Update data.
     * @param  array<string,mixed>       $package_data Package data.
     * @param  string                    $package_file Package file.
     * @param  array<string>             $locales      Supported locales.
     * @param  Handles_Updates           $updater      Update handler.
     * @return false|array<string,mixed>
     */
    private function update_callback(
        bool|array $update_data,
        array $package_data,
        string $package_file,
        array $locales,
        Handles_Updates $updater,
    ): array|bool {
        if ( $update_data ) {
            return $update_data;
        }

        return $updater->get_update_data( $package_file, $locales );
    }

    /**
     * Get the API callback.
     *
     * @param  object|array<string,mixed>|bool $package_info Package info.
     * @param  string                          $action       Action. Either `plugin_information` or `theme_information`.
     * @param  object                          $args         API arguments.
     * @param  Handles_Updates                 $updater      Update handler.

     * @return bool|array<string,mixed>|object
     */
    private function api_callback(
        object|array|bool $package_info,
        $action,
        object $args,
        Handles_Updates $updater,
    ): bool|array|object {
        if ( ! \in_array( $action, $this->api, true ) || $package_info ) {
            return $package_info;
        }

        return (object) $updater->get_update_data( $args->slug, array( $args->locale ?? \get_locale() ) );
    }
}
