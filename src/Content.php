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

        // First, let's check the tag attributes
        if (preg_match('#width=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $width = $matches[1];
        }

        if (preg_match('#height=["|\']?([\d%]+)["|\']?#i', $tag, $matches)) {
            $height = $matches[1];
        }

        if (strpos($width, '%') !== false && strpos($height, '%') !== false) {
            $width = $height = null;
        }

        // Second, let's check for size class
        if (preg_match('#class=["|\']?[^"\']*size-([^"\'\s]+)[^"\']*["|\']?#i', $tag, $size)) {
            $size = array_pop($size);

            if ($width === null && $height === null && $size !== 'full') {

            }
        }

        if (preg_match('#class=["|\']?[^"\']*wp-image-([\d]+)[^"\']*["|\']?#i', $tag, $attachmentId)) {
            $attachmentId = array_pop($attachmentId);

            $attachment = get_post($attachmentId);

            if ($attachment && !is_wp_error($attachment) && $attachment->post_type === 'attachment') {

            }
        }

        return compact('width', 'height');
    }
}
