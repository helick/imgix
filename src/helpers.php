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
 * Get the imgix supported formats.
 *
 * @return array
 */
function supported_formats(): array
{
    /**
     * Control the imgix supported formats.
     *
     * @param array $supportedFormats
     */
    $supportedFormats = apply_filters('helick_imgix_supported_formats', ['gif', 'jpg', 'jpeg', 'png']);
    $supportedFormats = (array)$supportedFormats;

    return $supportedFormats;
}

/**
 * Check whether the given format is supported.
 *
 * @param string $format
 *
 * @return bool
 */
function is_supported_format(string $format): bool
{
    return in_array($format, supported_formats(), true);
}

/**
 * Check whether the given image url is processable.
 *
 * @param string $imageUrl
 *
 * @return bool
 */
function is_processable_image_url(string $imageUrl): bool
{
    if (strpos($imageUrl, wp_upload_dir()['baseurl']) !== 0) {
        return false;
    }

    $imageUrlPath = parse_url($imageUrl, PHP_URL_PATH);

    $imageFormat = pathinfo($imageUrlPath, PATHINFO_EXTENSION);
    $imageFormat = strtolower($imageFormat);

    if (!is_supported_format($imageFormat)) {
        return false;
    }

    /**
     * Control whether the given image url is processable.
     *
     * @param bool   $isProcessable
     * @param string $imageUrl
     * @param array  $args
     */
    $isProcessable = apply_filters('helick_imgix_image_url_processable', true, $imageUrl, $args);
    $isProcessable = (bool)$isProcessable;

    return $isProcessable;
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
    if (!is_processable_image_url($imageUrl)) {
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
