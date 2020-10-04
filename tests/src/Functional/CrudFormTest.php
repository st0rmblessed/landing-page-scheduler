<?php

namespace Drupal\Tests\landing_page_scheduler\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test module.
 *
 * @group multiple_select
 */
class CrudFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'landing_page_scheduler',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * Test access to configuration page.
   */
  public function testCanAccessConfigPage() {
    $account = $this->drupalCreateUser([
      'access landing page scheduler config page',
      'access content',
    ]);

    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/system/landing-page-scheduler');
    $this->assertText('Landing Page Scheduler Helper');
  }

}
