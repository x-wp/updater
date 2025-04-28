<?php //phpcs:disable Squiz.Commenting.FunctionComment.Missing

namespace XWP\Updater;

use WP_Error;
use XWP\Updater\Interfaces\Handles_Updates;

/**
 * Base
 */
abstract class Handler_Base implements Handles_Updates {
    /**
     * Get the update URI
     *
     * @param string $slug Package slug.
     */
    abstract protected function get_update_uri( string $slug ): string;

    public function get_update_data( string $package_file, array $locales ): array|bool {
        $slug = $this->get_slug( $package_file );
        $data = $this->send_request( $slug );

        return $this->validate_response( $data )
            ? \json_decode( \wp_remote_retrieve_body( $data ), true )
            : false;
    }

    /**
     * Get the package slug
     *
     * @param  string $package_file Package file.
     * @return string
     */
    protected function get_slug( string $package_file ): string {
        if ( ! \str_contains( $package_file, '/' ) ) {
            return $package_file;
        }

        return \explode( '/', $package_file )[0];
    }

    /**
     * Get headers for the update request
     *
     * @return array<string,string>
     */
    protected function get_headers(): array {
        return array();
    }

    /**
     * Send the update request to the repo
     *
     * @param  string $slug   The slug.
     * @return array<string,mixed>|WP_Error The response or WP_Error on failure
     */
    protected function send_request( string $slug ): array|\WP_Error {
        return \wp_remote_get(
            $this->get_update_uri( $slug ),
            array(
                'headers' => $this->get_headers(),
                'timeout' => 10,
            ),
        );
    }

    /**
     * Validates the response
     *
     * @param array<string,mixed>|WP_Error $response The response.
     * @return bool
     */
    protected function validate_response( array|\WP_Error $response ): bool {
        return ! \is_wp_error( $response )
            && 200 === \wp_remote_retrieve_response_code( $response )
            && \wp_remote_retrieve_body( $response );
    }
}
