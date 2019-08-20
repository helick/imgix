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

        foreach ($images['img_tags'] as $index => $tag) {
            $transform = 'resize';

            $attachmentId = null;

            $fullSizeUrl = null;

            $src = $srcOrig = $images['img_urls'][$index];

            if (!apply_filters('imgix_image_url_processable', true, $src))
                continue;
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
            . '?(?P<img_tags><img[^>]+?src=["|\'](?P<img_urls>[^\s]+?)["|\'].*?>){1}'
            . '(?:\s*</a>)?#is';

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
}
