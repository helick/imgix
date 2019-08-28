<?php

namespace Helick\Imgix;

use Helick\Contracts\Bootable;

final class Content implements Bootable
{
    /**
     * Boot the service.
     *
     * @return void
     */
    public static function boot(): void
    {
        $self = new static;

        add_filter('the_content', [$self, 'parse'], 100);
    }

    /**
     * Parse a given content.
     *
     * @param string $content
     *
     * @return string
     */
    public function parse(string $content): string
    {
        if (!$images = $this->resolveImages($content)) {
            return $content;
        }

        if ($attachmentIds = $this->resolveAttachmentIds($images['img_tags'])) {
            _prime_post_caches($attachmentIds, false, true);
        }

        foreach ($images['img_tags'] as $index => $imageTag) {
            $imageUrl = $images['img_urls'][$index];

            if (!is_processable_image_url($imageUrl)) {
                continue;
            }

            [$imageWidth, $imageHeight] = $this->resolveImageSize($imageTag);

            var_dump($imageUrl, $imageWidth, $imageHeight);
            die;
        }

        return $content;
    }

    /**
     * @param string $content
     *
     * @return array
     */
    private function resolveImages(string $content): array
    {
        $pattern = '#(?:<a[^>]+?href=["|\'](?P<link_urls>[^\s]+?)["|\'][^>]*?>\s*)'
            . '?(?P<img_tags><(?:img|amp-img|amp-anim)[^>]*?\s+?src=["|\']'
            . '(?P<img_urls>[^\s]+?)["|\'].*?>){1}(?:\s*</a>)?#is';

        if (!preg_match_all($pattern, $content, $images)) {
            return [];
        }

        $images = array_filter($images, 'is_string', ARRAY_FILTER_USE_KEY);

        return $images;
    }

    /**
     * @param array $tags
     *
     * @return array
     */
    private function resolveAttachmentIds(array $tags): array
    {
        $attachmentIds = [];

        foreach ($tags as $tag) {
            if (preg_match('/wp-image-([\d]+)/i', $tag, $matches)) {
                $attachmentIds[(int)$matches[1]] = true;
            }
        }

        return array_keys($attachmentIds);
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    private function resolveImageSize(string $tag): array
    {
        $width = $height = null;
        $crop  = false;

        // First, let's check the tag attributes for width and height
        if (preg_match('#width=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $width = array_pop($matches);
        } else {
            unset($matches);
        }

        if (preg_match('#height=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $height = array_pop($matches);
        } else {
            unset($matches);
        }

        $isRelativeWidth  = strpos($width, '%') !== false;
        $isRelativeHeight = strpos($height, '%') !== false;

        if ($isRelativeWidth && $isRelativeHeight) {
            $width = $height = null;
        }

        // Second, let's check tag classes for a size
        if (preg_match('#class=["|\']?[^"\']*size-([^"\'\s]+)[^"\']*["|\']?#i', $tag, $size)) {
            $size = array_pop($size);

            $isUnsetWidth  = $width === null;
            $isUnsetHeight = $height === null;
            $isFullSize    = $size === 'full';

            if ($isUnsetWidth && $isUnsetHeight && !$isFullSize) {
                if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'], true)) {
                    $width  = (int)get_option($size . '_size_w');
                    $height = (int)get_option($size . '_size_h');
                    $crop   = (bool)get_option($size . '_crop');
                } elseif (isset($GLOBALS['_wp_additional_image_sizes'][$size])) {
                    $width  = $GLOBALS['_wp_additional_image_sizes'][$size]['width'];
                    $height = $GLOBALS['_wp_additional_image_sizes'][$size]['height'];
                    $crop   = $GLOBALS['_wp_additional_image_sizes'][$size]['crop'];
                }
            }
        } else {
            unset($size);
        }

        // Third, let's check for the attachment
        if (preg_match('#class=["|\']?[^"\']*wp-image-([\d]+)[^"\']*["|\']?#i', $tag, $attachmentId)) {
            $attachmentId = (int)array_pop($attachmentId);

            $attachment = get_post($attachmentId);

            if ($attachment && !is_wp_error($attachment) && $attachment->post_type === 'attachment') {
                [$attachmentUrl, $attachmentWidth, $attachmentHeight] = wp_get_attachment_image_src(
                    $attachmentId,
                    $size ?? 'full'
                );

                if (is_processable_image_url($attachmentUrl)) {
                    $hasBiggerWidth  = $width !== null && $width > $attachmentWidth;
                    $hasBiggerHeight = $height !== null && $height > $attachmentHeight;

                    if ($hasBiggerWidth || $hasBiggerHeight) {
                        $width  = $width === null ? null : min($width, $attachmentWidth);
                        $height = $height === null ? null : min($height, $attachmentHeight);
                    }

                    $isUnsetWidth  = $width === null;
                    $isUnsetHeight = $height === null;

                    if ($isUnsetWidth && $isUnsetHeight) {
                        $width  = $attachmentWidth;
                        $height = $attachmentHeight;
                        $crop   = false;
                    } elseif (isset($size)) {
                        if (in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'], true)) {
                            $crop = (bool)get_option($size . '_crop');
                        } elseif (isset($GLOBALS['_wp_additional_image_sizes'][$size])) {
                            $crop = $GLOBALS['_wp_additional_image_sizes'][$size]['crop'];
                        }
                    }
                }
            } else {
                unset($attachmentId, $attachment);
            }
        } else {
            unset($attachmentId);
        }

        return compact('width', 'height', 'crop');
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    private function resolveDimensionsFromTagAttributes(string $tag): array
    {
        $width = $height = null;

        if (preg_match('#width=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $width = array_pop($matches);
        } else {
            unset($matches);
        }

        if (preg_match('#height=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $height = array_pop($matches);
        } else {
            unset($matches);
        }

        $isRelativeWidth  = $width && strpos($width, '%') !== false;
        $isRelativeHeight = $height && strpos($height, '%') !== false;

        if ($isRelativeWidth && $isRelativeHeight) {
            $width = $height = null;
        }

        return [$width, $height];
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    private function resolveDimensionsFromTagClass(string $tag): array
    {
        $width = $height = null;
        $crop  = false;

        if (preg_match('#class=["|\']?[^"\']*size-([^"\'\s]+)[^"\']*["|\']?#i', $tag, $size)) {
            $size = array_pop($size);

            $isFullSize = $size === 'full';

            if (!$isFullSize) {
                $withinBuiltinSizes    = in_array($size, ['thumbnail', 'medium', 'medium_large', 'large'], true);
                $withinAdditionalSizes = isset($GLOBALS['_wp_additional_image_sizes'][$size]);

                if ($withinBuiltinSizes) {
                    $width  = (int)get_option($size . '_size_w');
                    $height = (int)get_option($size . '_size_h');
                    $crop   = (bool)get_option($size . '_crop');
                } elseif ($withinAdditionalSizes) {
                    $width  = $GLOBALS['_wp_additional_image_sizes'][$size]['width'];
                    $height = $GLOBALS['_wp_additional_image_sizes'][$size]['height'];
                    $crop   = $GLOBALS['_wp_additional_image_sizes'][$size]['crop'];
                }
            }
        } else {
            unset($size);
        }

        return [$width, $height, $crop];
    }

    /**
     * @param string $url
     *
     * @return array
     */
    private function resolveDimensionsFromUrl(string $url): array
    {
        $width = $height = null;

        if (preg_match('#-(\d+)x(\d+)\.(?:' . implode('|', supported_formats()) . '){1}$#i', $url, $matches)) {
            $width  = (int)$matches[1];
            $height = (int)$matches[2];
        } else {
            unset($matches);
        }

        return [$width, $height];
    }
}
