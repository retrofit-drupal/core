<?php

declare(strict_types=1);

use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\TermStorageInterface;
use Drupal\taxonomy\VocabularyInterface;
use Retrofit\Drupal\Entity\WrappedConfigEntity;
use Retrofit\Drupal\Entity\WrappedContentEntity;

function taxonomy_get_vocabularies(): array
{
    return array_map(
        static fn (VocabularyInterface $vocabulary) => new WrappedConfigEntity($vocabulary),
        Vocabulary::loadMultiple()
    );
}

function taxonomy_get_parents($tid)
{
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    return array_map(
        static fn (TermInterface $term) => new WrappedContentEntity($term),
        $storage->loadParents($tid)
    );
}

function taxonomy_get_tree($vid, $parent = 0, $max_depth = null, $load_entities = false)
{
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    return array_map(
        static fn (TermInterface $term) => new WrappedContentEntity($term),
        $storage->loadTree($vid, $parent, $max_depth, $load_entities)
    );
}
