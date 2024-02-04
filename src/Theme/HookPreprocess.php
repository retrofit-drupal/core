<?php

declare(strict_types=1);

namespace Retrofit\Drupal\Theme;

use Drupal\block\BlockInterface;
use Retrofit\Drupal\Entity\WrappedConfigEntity;

/**
 * @phpstan-type Variables array<string, string|array<int|string, mixed>>
 * @phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
 */
final class HookPreprocess
{
    /**
     * @param Variables $variables
     */
    public static function page(array &$variables): void
    {
        $variables['logo'] = theme_get_setting('logo.url');

        // @todo support in https://github.com/retrofit-drupal/retrofit/issues/43
        $variables['main_menu'] = [];
        $variables['secondary_menu'] = [];

        // Legacy variables replaced by blocks.
        $variables['title'] = $variables['page']['#title'] ?? '';
        $variables['breadcrumb'] = '';
        $variables['messages'] = '';
        $variables['tabs'] = '';
        $variables['action_links'] = '';
        $variables['feed_icons'] = '';
    }

    /**
     * @param Variables $variables
     */
    public static function maintenance_page(array &$variables): void
    {
        self::page($variables);
    }

    /**
     * @param Variables $variables
     */
    public static function block(array &$variables): void
    {
        // @todo find a way to do this earlier.
        // \Drupal\block\BlockViewBuilder::preRender removes the block
        // after building the plugin. This is within a lazy builder
        // that is called through a class name and method callable,
        // not a service.
        $block = \Drupal::entityTypeManager()
            ->getStorage('block')
            ->load($variables['elements']['#id']);
        assert($block instanceof BlockInterface);
        $block_counter = &drupal_static('template_preprocess_block', []);
        assert(is_array($block_counter));
        $variables['block'] = new WrappedConfigEntity($block);
        if (!isset($block_counter[$block->getRegion()])) {
            $block_counter[$block->getRegion()] = 1;
        }
        $variables['block_zebra'] = $block_counter[$block->getRegion()] % 2 ? 'odd' : 'even';
        $variables['block_id'] = $block_counter[$block->getRegion()]++;
        $variables['classes_array'][] = drupal_html_class('block-' . $variables['configuration']['provider']);
        $variables['classes'] = implode(' ', $variables['classes_array']);
        $variables['theme_hook_suggestions'][] = 'block__' . $block->getRegion();
        $variables['theme_hook_suggestions'][] = 'block__' . $variables['configuration']['provider'];
        $variables['theme_hook_suggestions'][] = 'block__' . $variables['configuration']['provider']
        . '__' . strtr($block->getPluginId(), '-', '_');
        $variables['block_html_id'] = $variables['attributes']['id'];
    }

    /**
     * @param array{
     *      links?: array{title: string, html?: bool, href?: string, attributes?: array{class?: string[]}},
     *      heading?: mixed[]
     * } $variables
     */
    public static function links(array &$variables): void
    {
        if (!empty($variables['links'])) {
            foreach ($variables['links'] as $key => &$link) {
                $link += ['attributes' => []];
                $link['attributes']['class'][] = $key;
                if (isset($link['html']) && !empty($link['html'])) {
                    $link['title'] = ['#markup' => $link['title']];
                    unset($link['html']);
                }
                if (
                    isset($link['href'])
                    && ($url = \Drupal::pathValidator()->getUrlIfValidWithoutAccessCheck($link['href']))
                ) {
                    $url->mergeOptions($link);
                    $link['url'] = $url;
                    unset($link['href']);
                }
            }
        }
        if (!empty($variables['heading'])) {
            if (!empty($variables['heading']['class'])) {
                $variables['heading']['attributes']['class'] = $variables['heading']['class'];
                unset($variables['heading']['class']);
            }
        }
    }
}
