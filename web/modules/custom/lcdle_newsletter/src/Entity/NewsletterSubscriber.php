<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the Newsletter Subscriber content entity.
 *
 * Stores newsletter subscriptions independently of Drupal user accounts so
 * that anonymous subscribers (RGPD-compliant) are fully supported.
 */
#[ContentEntityType(
  id: 'newsletter_subscriber',
  label: new TranslatableMarkup('Newsletter Subscriber'),
  label_collection: new TranslatableMarkup('Newsletter Subscribers'),
  label_singular: new TranslatableMarkup('newsletter subscriber'),
  label_plural: new TranslatableMarkup('newsletter subscribers'),
  base_table: 'newsletter_subscriber',
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'email',
  ],
)]
class NewsletterSubscriber extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * @return array<string, \Drupal\Core\Field\FieldDefinitionInterface>
   *   Array of base field definitions keyed by field name.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Email address — unique per subscriber.
    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(new TranslatableMarkup('Email'))
      ->setDescription(new TranslatableMarkup('The subscriber email address.'))
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 0,
      ]);

    // Subscription status.
    $fields['status_value'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Status'))
      ->setDescription(new TranslatableMarkup('The subscription status.'))
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending'       => new TranslatableMarkup('Pending'),
        'active'        => new TranslatableMarkup('Active'),
        'unsubscribed'  => new TranslatableMarkup('Unsubscribed'),
        'bounced'       => new TranslatableMarkup('Bounced'),
      ]);

    // Unique unsubscribe token (64 characters).
    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Token'))
      ->setDescription(new TranslatableMarkup('The unique token used for one-click unsubscription.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->addConstraint('UniqueField');

    // Timestamp of when the user completed the opt-in (semantic field).
    $fields['subscribed_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(new TranslatableMarkup('Subscribed at'))
      ->setDescription(new TranslatableMarkup('The timestamp when the subscription was confirmed.'));

    // Subscription source (e.g. 'form', 'api', 'import').
    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Source'))
      ->setDescription(new TranslatableMarkup('How the subscription was created.'))
      ->setSetting('max_length', 60)
      ->setDefaultValue('form');

    // Locale / language code preferred by the subscriber.
    $fields['locale'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Locale'))
      ->setDescription(new TranslatableMarkup('The preferred locale for newsletter delivery.'))
      ->setSetting('max_length', 12)
      ->setDefaultValue('fr');

    // Optional reference to a Drupal user (for per-author newsletters).
    $fields['author_scope'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Author scope'))
      ->setDescription(new TranslatableMarkup('Optional user account this subscription is scoped to.'))
      ->setRequired(FALSE)
      ->setSetting('target_type', 'user');

    // Technical creation timestamp.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup('The time the record was created.'));

    // Last modification timestamp (maintained by EntityChangedTrait).
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup('The time the record was last changed.'));

    return $fields;
  }

}
