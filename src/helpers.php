<?php

namespace Helick\Imgix;

use Imgix\UrlBuilder;

/**
 * Get the imgix domain.
 *
 * @return string
 */
function domain(): string
{
    return defined('IMGIX_DOMAIN')
        ? IMGIX_DOMAIN
        : '';
}

/**
 * Get the imgix url.
 *
 * @param string $imageUrl
 * @param array  $args
 * @param bool   $useHttps
 * @param string $signKey
 *
 * @return string
 */
function url(string $imageUrl, array $args = [], bool $useHttps = true, string $signKey = ''): string
{
    $requiresProcessing = strpos($imageUrl, wp_upload_dir()['baseurl']) === 0;

    /**
     * Control whether the given image url is processable.
     *
     * @param bool   $isProcessable
     * @param string $imageUrl
     * @param array  $args
     */
    $isProcessable = apply_filters('helick_imgix_image_url_processable', true, $imageUrl, $args);
    $isProcessable = (bool)$isProcessable;

    if (!$requiresProcessing || !$isProcessable) {
        return $imageUrl;
    }

    $imageUrlPath = parse_url($imageUrl, PHP_URL_PATH);

    $imageFile = basename($imageUrlPath);
    $imageFile = urlencode($imageFile);

    $imageUrlPath = str_replace($imageUrl, $imageFile, $imageUrlPath);

    /**
     * Control the imgix image url path.
     *
     * @param string $imageUrlPath
     * @param array  $args
     */
    $imageUrlPath = apply_filters('helick_imgix_image_url_path', $imageUrlPath, $args);

    /**
     * Control the imgix arguments.
     *
     * @param array  $args
     * @param string $imageUrlPath
     */
    $args = apply_filters('helick_imgix_args', $args, $imageUrlPath);

    $urlBuilder = new UrlBuilder(domain(), $useHttps, $signKey);
    $imgixUrl   = $urlBuilder->createURL($imageUrlPath, $args);

    /**
     * Control the imgix url.
     *
     * @param string $imgixUrl
     * @param string $imageUrl
     * @param array  $args
     */
    $imgixUrl = apply_filters('helick_imgix_url', $imgixUrl, $imageUrl, $args);

    return $imgixUrl;
}
