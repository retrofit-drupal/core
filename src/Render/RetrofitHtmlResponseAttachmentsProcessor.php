<?php

declare(strict_types=1);

namespace Retrofit\Drupal\Render;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Render\AttachmentsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Retrofit\Drupal\Asset\RetrofitJsCollectionRenderer;
use Retrofit\Drupal\Asset\RetrofitLibraryDiscovery;

final class RetrofitHtmlResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface
{
    public function __construct(
        private readonly AttachmentsResponseProcessorInterface $inner,
        private readonly AssetCollectionRendererInterface $jsCollectionRenderer,
        private readonly LibraryDiscoveryInterface $libraryDiscovery,
    ) {
    }

    public function processAttachments(AttachmentsInterface $response)
    {
        $attachments = $response->getAttachments();
        if (isset($attachments['library']) && is_array($attachments['library'])) {
            foreach ($attachments['library'] as $key => $item) {
                if (is_array($item)) {
                    $item[0] = match ($item[0]) {
                        'drupal.ajax', 'jquery' => 'core',
                        default => $item[0],
                    };
                    $attachments['library'][$key] = implode('/', $item);
                }
            }
        }
        $retrofit_library = [];
        if (isset($attachments['css']) && is_array($attachments['css'])) {
            foreach ($attachments['css'] as $key => $item) {
                if (is_array($item) && isset($item['type'], $item['data']) && $item['type'] === 'inline') {
                    $element = [
                        '#tag' => 'style',
                        '#value' => $item['data'],
                        '#weight' => $item['weight'] ?? 0,
                    ];
                    unset(
                        $item['data'],
                        $item['type'],
                        $item['basename'],
                        $item['group'],
                        $item['every_page'],
                        $item['weight'],
                        $item['preprocess'],
                        $item['browsers'],
                    );
                    $element['#attributes'] = $item;
                    $attachments['html_head'][] = [
                        $element,
                        "retrofit:$key",
                    ];
                    unset($attachments['css'][$key]);
                }
            }
            $retrofit_library['css'] = $attachments['css'];
            unset($attachments['css']);
            asort($retrofit_library['css']);
        }
        if (isset($attachments['js']) && is_array($attachments['js'])) {
            $requires_jquery = false;
            foreach ($attachments['js'] as $key => &$item) {
                if (is_array($item)) {
                    $requires_jquery = !empty($item['requires_jquery']) ?: $requires_jquery;
                    switch ($item['type'] ?? 'file') {
                        case 'inline':
                            $element = [
                                '#tag' => 'script',
                                '#value' => $item['data'] ?? $key,
                                '#weight' => $item['weight'] ?? 0,
                            ];
                            $scope = $item['scope'] ?? 'footer';
                            unset(
                                $item['data'],
                                $item['type'],
                                $item['scope'],
                                $item['group'],
                                $item['every_page'],
                                $item['weight'],
                                $item['requires_jquery'],
                                $item['cache'],
                                $item['preprocess'],
                            );
                            $element['#attributes'] = $item;
                            unset($attachments['js'][$key]);
                            switch ($scope) {
                                case 'header':
                                    $attachments['html_head'][] = [
                                        $element,
                                        "retrofit:$key",
                                    ];
                                    break;

                                default:
                                    $element['#type'] = 'html_tag';
                                    assert($this->jsCollectionRenderer instanceof RetrofitJsCollectionRenderer);
                                    $this->jsCollectionRenderer->addRetrofitFooter($element);
                                    break;
                            }
                            break;

                        case 'setting':
                            if (isset($item['data'])) {
                                $attachments['drupalSettings'] = NestedArray::mergeDeepArray(
                                    [$attachments['drupalSettings'] ?? [], $item['data']],
                                    true,
                                );
                            }
                            unset($attachments['js'][$key]);
                            break;

                        default:
                            $item['attributes']['defer'] = $item['defer'] ?? false;
                            break;
                    }
                }
            }
            assert($this->jsCollectionRenderer instanceof RetrofitJsCollectionRenderer);
            $this->jsCollectionRenderer->addRetrofitFooter([
                '#type' => 'html_tag',
                '#tag' => 'script',
                '#value' => 'Drupal.settings = drupalSettings;',
            ]);
            $retrofit_library['requires_jquery'] = $requires_jquery;
            $retrofit_library['js'] = $attachments['js'];
            unset($attachments['js']);
            asort($retrofit_library['js']);
        }
        $name = Crypt::hashBase64(serialize($retrofit_library));
        assert($this->libraryDiscovery instanceof RetrofitLibraryDiscovery);
        $this->libraryDiscovery->setRetrofitLibrary($name, $retrofit_library);
        $attachments['library'][] = "retrofit/$name";
        $response->setAttachments($attachments);
        return $this->inner->processAttachments($response);
    }
}
