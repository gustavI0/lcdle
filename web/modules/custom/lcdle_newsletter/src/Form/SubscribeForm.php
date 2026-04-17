<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\lcdle_newsletter\Service\TokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the newsletter subscribe form at /newsletter.
 *
 * On submission the form creates a NewsletterSubscriber entity in "pending"
 * status. Duplicate emails are silently ignored to prevent email enumeration.
 * The confirmation URL is displayed on-screen (MVP — actual email delivery
 * is deferred to Phase 3).
 */
class SubscribeForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The token generator service.
   *
   * @var \Drupal\lcdle_newsletter\Service\TokenGenerator
   */
  protected TokenGenerator $tokenGenerator;

  /**
   * Constructs a SubscribeForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\lcdle_newsletter\Service\TokenGenerator $token_generator
   *   The newsletter token generator.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TokenGenerator $token_generator,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tokenGenerator = $token_generator;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   *
   * @return static
   *   A new instance of this form.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('lcdle_newsletter.token_generator'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @return string
   *   The unique form ID.
   */
  public function getFormId(): string {
    return 'lcdle_newsletter_subscribe';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   *
   * @return array<string, mixed>
   *   The complete form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Adresse e-mail'),
      '#required' => TRUE,
      '#maxlength' => 254,
    ];

    $form['consent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("J'accepte de recevoir la newsletter et je reconnais avoir pris connaissance de la politique de confidentialité."),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("S'abonner"),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form structure array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $email = $form_state->getValue('email');

    // Check for an existing subscriber to prevent email enumeration.
    $existing = $this->entityTypeManager
      ->getStorage('newsletter_subscriber')
      ->loadByProperties(['email' => $email]);

    if (!empty($existing)) {
      // Silent rejection: show the same success message as for a new subscriber.
      $this->messenger()->addStatus(
        $this->t('Merci ! Un e-mail de confirmation vous a été envoyé.')
      );
      return;
    }

    // Generate a unique token and create the subscriber entity.
    $token = $this->tokenGenerator->generate();

    $subscriber = $this->entityTypeManager
      ->getStorage('newsletter_subscriber')
      ->create([
        'email' => $email,
        'status_value' => 'pending',
        'token' => $token,
        'source' => 'form',
        'locale' => 'fr',
      ]);
    $subscriber->save();

    // Build the confirmation URL for MVP on-screen display.
    $confirm_url = Url::fromRoute('lcdle_newsletter.confirm', ['token' => $token])
      ->setAbsolute()
      ->toString();

    $this->messenger()->addStatus(
      $this->t('Merci ! Un e-mail de confirmation vous a été envoyé. (MVP — lien de confirmation : @url)', [
        '@url' => $confirm_url,
      ])
    );
  }

}
