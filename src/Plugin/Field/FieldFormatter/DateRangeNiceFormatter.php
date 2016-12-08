<?php

namespace Drupal\datetime_extras\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldFormatter\DateTimeDefaultFormatter;
use Drupal\datetime_extras\DateTimeExtraTrait;

/**
 * Plugin implementation of the 'Default' formatter for 'daterange' fields.
 *
 * This formatter renders the data range using <time> elements, with
 * configurable date formats (from the list of configured formats) and a
 * separator.
 *
 * @FieldFormatter(
 *   id = "daterange_nice",
 *   label = @Translation("Nice"),
 *   field_types = {
 *     "daterange"
 *   }
 * )
 */
class DateRangeNiceFormatter extends DateTimeDefaultFormatter {

  use DateTimeExtraTrait;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'separator' => '-',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $separator = $this->getSetting('separator');

    foreach ($items as $delta => $item) {
      if (!empty($item->start_date) && !empty($item->end_date)) {
        /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
        $start_date = $item->start_date;
        /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
        $end_date = $item->end_date;
        $format_types = $this->niceFormats();

        if ($start_date->format('U') !== $end_date->format('U')) {
          $format_start =
          $format_end = $format_types[$this->getSetting('format_type')]['format'];
          if ($start_date->format('Y-m-d') == $end_date->format('Y-m-d')) {
            $format_start = $format_types[$this->getSetting('format_type')]['format_time_start'];
            $format_end = $format_types[$this->getSetting('format_type')]['format_time_end'];
          }
          else if ($start_date->format('Y-m') == $end_date->format('Y-m')) {
            $format_start = $format_types[$this->getSetting('format_type')]['format_day_start'];
            $format_end = $format_types[$this->getSetting('format_type')]['format_day_end'];
          }
          else if ($start_date->format('Y') == $end_date->format('Y')) {
            $format_start = $format_types[$this->getSetting('format_type')]['format_month_start'];
            $format_end = $format_types[$this->getSetting('format_type')]['format_month_end'];
          }
          else {
            $format_start = $format_types[$this->getSetting('format_type')]['format_year_start'];
            $format_end = $format_types[$this->getSetting('format_type')]['format_year_end'];
          }
          $elements[$delta] = [
            'start_date' => $this->buildDateWithIsoAttribute($start_date, $format_start),
            'separator' => ['#plain_text' => ' ' . $separator . ' '],
            'end_date' => $this->buildDateWithIsoAttribute($end_date, $format_end),
          ];
        }
        else {
          if (!empty($item->_attributes)) {
            $elements[$delta]['#attributes'] += $item->_attributes;
            // Unset field item attributes since they have been included in the
            // formatter output and should not be rendered in the field template.
            unset($item->_attributes);
          }
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatDate($date, $format = NULL) {
    $format_type = 'custom';
    $format = $format ? $format : 'm/d/Y';
    $timezone = $this->getSetting('timezone_override') ?: $date->getTimezone()->getName();
    return $this->dateFormatter->format($date->getTimestamp(), $format_type, $format, $timezone != '' ? $timezone : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $time = new DrupalDateTime();
    $format_types = $this->niceFormats();
    $options = [];
    foreach ($format_types as $type => $type_info) {
      $format = $this->dateFormatter->format($time->format('U'), 'custom', $type_info['format']);
      $options[$type] = $type_info['label'] . ' (' . $format . ')';
    }

    $form['format_type'] = array(
      '#type' => 'select',
      '#title' => t('Date format'),
      '#description' => t("Choose a format for displaying the date. Be sure to set a format appropriate for the field, i.e. omitting time for a field that only has a date."),
      '#options' => $options,
      '#default_value' => $this->getSetting('format_type'),
    );

    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Date separator'),
      '#description' => $this->t('The string to separate the start and end dates'),
      '#default_value' => $this->getSetting('separator'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    if ($separator = $this->getSetting('separator')) {
      $summary[] = $this->t('Separator: %separator', ['%separator' => $separator]);
    }

    return $summary;
  }

  public function niceFormats() {
    $formats = [];
    $formats['nice_long'] = [
      'label' => t('Nice long'),
      'format' => 'F d, Y - h:i A',
      'format_time_start' => 'F d, Y - h:i A',
      'format_time_end' => 'h:i A',
      'format_day_start' => 'F d',
      'format_day_end' => 'd, Y',
      'format_month_start' => 'F d',
      'format_month_end' => 'F d, Y',
      'format_year_start' => 'F d, Y',
      'format_year_end' => 'F d, Y',
    ];
    $formats['nice_short'] = [
      'label' => t('Nice short'),
      'format' => 'm/d/Y - h:i a',
      'format_time_start' => 'm/d/Y - h:i a',
      'format_time_end' => 'h:i a',
      'format_day_start' => 'm/d',
      'format_day_end' => 'd/Y',
      'format_month_start' => 'm/d',
      'format_month_end' => 'm/d/Y',
      'format_year_start' => 'm/d/Y',
      'format_year_end' => 'm/d/Y',
    ];
    return $formats;
  }

}
