<?php
/**
 * Handles_Updates interface file.
 *
 * @package    eXtended WordPress
 * @subpackage Updater
 */

namespace XWP\Updater\Interfaces;

/**
 * Describes handlers which get update data from a remote server.
 */
interface Handles_Updates {
    /**
     * Get the update data for a specific package.
     *
     * @param  string        $package_file Package file.
     * @param  array<string> $locales      Supported locales.
     * @return false|array{
     *   id: string,
     *   slug: string,
     *   version: string,
     *   url: string,
     *   package: string,
     *   homepage: string,
     *   download_link: string,
     *   tested: string,
     *   requires_php: string,
     *   auto_update: bool,
     *   last_updated: string,
     *   icons?: array{
     *     svg?: string,
     *     '1x'?: string,
     *     '2x'?: string,
     *   },
     *   banners?: array{
     *     low?: string,
     *     high?: string
     *   },
     *   banners_rtl?: array{
     *     low?: string,
     *     high?: string
     *   },
     *   sections?: array{
     *     description?: string,
     *     installation?: string,
     *     changelog?: string,
     *     screenshots?: string,
     *     faq?: string,
     *     reviews?: string,
     *   },
     *   contributors?: array<string,array{
     *     display_name?: string,
     *     profile?: string,
     *     avatar?: string
     *   }>,
     *   translations?: array<array{
     *     language: string,
     *     version: string,
     *     package: string,
     *     updated: string,
     *     autoupdate: string,
     *   }>
     * }
     */
    public function get_update_data( string $package_file, array $locales ): array|bool;
}
