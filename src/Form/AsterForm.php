<?php

namespace Drupal\aster\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Table Form Class.
 */
class AsterForm extends FormBase {

  /**
   * Number of tables.
   *
   * @var int
   */
  protected int $tables = 1;

  /**
   * Number of rows.
   *
   * @var int
   */
  protected int $rows = 1;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aster_attestation';
  }

  /**
   * Building form.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="form-wrapper">';
    $form['#suffix'] = '</div>';

    $form['add_year'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Year'),
      '#submit' => ['::addRows'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    $form['add_table'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Table'),
      '#submit' => ['::addTable'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    // Call a function that build a headlines of our tables.
    $this->buildHeadlines();
    // Call a function that build columns and rows of our tables.
    $this->buildTables($form, $form_state);

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxReload',
        'event' => 'click',
        'wrapper' => 'form-wrapper',
        'progress' => [
          'type' => 'none',
        ],
      ],
    ];
    // Add Styles.
    $form['#attached']['library'][] = 'aster/aster_library';
    return $form;
  }

  /**
   * Building headlines.
   * Active and inactive cells.
   */
  protected function buildHeadlines() {
    $this->headlines = [
      'year' => $this->t('Year'),
      'jan' => $this->t('Jan'),
      'feb' => $this->t('Feb'),
      'mar' => $this->t('Mar'),
      'q1' => $this->t('Q1'),
      'apr' => $this->t('Apr'),
      'may' => $this->t('May'),
      'jun' => $this->t('Jun'),
      'q2' => $this->t('Q2'),
      'jul' => $this->t('Jul'),
      'aug' => $this->t('Aug'),
      'sep' => $this->t('Sep'),
      'q3' => $this->t('Q3'),
      'oct' => $this->t('Oct'),
      'nov' => $this->t('Nov'),
      'dec' => $this->t('Dec'),
      'q4' => $this->t('Q4'),
      'ytd' => $this->t('YTD'),
    ];
    $this->titles = [
      'year' => $this->t('Year'),
      'q1' => $this->t('Q1'),
      'q2' => $this->t('Q2'),
      'q3' => $this->t('Q3'),
      'q4' => $this->t('Q4'),
      'ytd' => $this->t('YTD'),
    ];
  }

  /**
   * Function adds a new table.
   */
  public function buildTables(array &$form, FormStateInterface $form_state) {
    // Loop for enumeration tables.
    for ($i = 0; $i < $this->tables; $i++) {
      $table_id = $i;
      // Set special attributes for each table.
      $form[$table_id] = [
        '#type' => 'table',
        // Call headlined from our function for the header.
        '#header' => $this->headlines,
        '#tree' => 'TRUE',
      ];
      // Call a function that adds a new rows.
      $this->buildTableRows($table_id, $form[$table_id], $form_state);
    }
  }

  /**
   * Function adds rows to the existing table.
   */
  protected function buildTableRows($table_id, array &$cells, FormStateInterface $form_state): void {
    // Loop for enumeration rows.
    for ($i = $this->rows; $i > 0; $i--) {
      foreach ($this->headlines as $main => $value) {
        // Set special attributes for each cell.
        $cells[$i][$main] = [
          '#type' => 'number',
          '#step' => '0.01',
        ];
        if (array_key_exists($main, $this->titles)) {
          //Setting the default value to inactive cells.
          $defaultValue = $form_state->getValue($table_id . '][' . $i . '][' . $main, 0);
          $cells[$i][$main]['#default_value'] = round($defaultValue, 2);
          // Disable inactive cells.
          $cells[$i][$main]['#disabled'] = TRUE;
        }
      }
      $cells[$i]['year']['#default_value'] = date('Y') - $i + 1;
    }
  }

  /**
   * Function that adds another row.
   */
  public function addRows(array &$form, FormStateInterface $form_state): array {
    // Getting current amount of rows and increasing it.
    $this->rows++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Function that adds another table.
   */
  public function addTable(array &$form, FormStateInterface $form_state): array {
    // Getting current amount of tables and increasing it.
    $this->tables++;
    $form_state->setRebuild();
    return $form;
  }

  /**
   * Function that gets values from the table.
   */
  public function getClearArrayValues(array $valueTableCell): array {
    // For adding values from cells in the table.
    $values = [];
    // Call inactive cells of the table.
    $inactive_cells = $this->titles;
    // Go through rows.
    for ($i = $this->rows; $i >= 0; $i--) {
      // Go through rows' values.
      foreach ($valueTableCell[$i] as $key => $active_cells) {
        if (!array_key_exists($key, $inactive_cells)) {
          $values[] = $active_cells;
        }
      }
    }
    return $values;
  }

  /**
   * Function that checks if value is not empty.
   */
  public function notEmpty($active_cells): bool {
    return ($active_cells || $active_cells == '0');
  }

  /**
   * Validating the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Getting values from the table.
    $table_values = $form_state->getValues();
    // An array in which are sorted values from the tables.
    $active_values = [];
    // Start point of validation.
    $start_point = NULL;
    // End point of validation.
    $end_point = NULL;
    // Loop for the all tables.
    for ($i = 0; $i < $this->tables; $i++) {
      // Call the function that gets values from the table.
      $cell_values = $this->getClearArrayValues($table_values[$i]);
      // An array in which are saved and sorted values from the tables.
      $active_values[] = $cell_values;
      //Go through cells.
      foreach ($cell_values as $key => $active_cells) {
        // Comparing cells in the tables.
        for ($table_cell = 0; $table_cell <= count($active_values[$i]) - 1; $table_cell++) {
          if ($this->notEmpty($active_values[0][$table_cell]) !== $this->notEmpty($active_values[$i][$table_cell])) {
            $form_state->setErrorByName($i, 'Tables are different. Please, check.');
          }
        }
        // Value of the start point of the key if the cell is not empty.
        if (!empty($active_cells)) {
          $start_point = $key;
          break;
        }
      }
      // If value of the start point exist, run the loop.
      if ($start_point !== NULL) {
        // Going into all filled cells after start point.
        for ($filled_cell = $start_point; $filled_cell < count($cell_values); $filled_cell++) {
          // End point if filled cells are empty.
          if (($cell_values[$filled_cell] == NULL)) {
            $end_point = $filled_cell;
            break;
          }
        }
      }
      // If value of the end point exist, run the loop.
      if ($end_point !== NULL) {
        // Going into all filled cells after end point.
        for ($cell = $end_point; $cell < count($cell_values); $cell++) {
          // If value of the cell is not equal to null.
          if (($cell_values[$cell]) != NULL) {
            $form_state->setErrorByName("table-$i", 'Invalid');
          }
        }
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Getting values from the table.
    $result = $form_state->getValues();
    foreach ($result as $table_id => $cells) {
      foreach ($cells as $row_id => $value) {
        $my_cell = $table_id . '][' . $row_id . '][';
        // Calculate quarter and year values.
        $q1 = (($value['jan'] + $value['feb'] + $value['mar']) + 1) / 3;
        $q2 = (($value['apr'] + $value['may'] + $value['jun']) + 1) / 3;
        $q3 = (($value['jul'] + $value['aug'] + $value['sep']) + 1) / 3;
        $q4 = (($value['oct'] + $value['nov'] + $value['dec']) + 1) / 3;
        $ytd = (($q1 + $q2 + $q3 + $q4) + 1) / 4;
        // Set our values to the cells in the table.
        $form_state->setValue($my_cell . 'q1', $q1);
        $form_state->setValue($my_cell . 'q2', $q2);
        $form_state->setValue($my_cell . 'q3', $q3);
        $form_state->setValue($my_cell . 'q4', $q4);
        $form_state->setValue($my_cell . 'ytd', $ytd);
      }
    }
    $this->messenger()->addStatus('Valid');
    $form_state->setRebuild();
  }

  /**
   * Function for refreshing form.
   */
  public function ajaxReload(array &$form, FormStateInterface $form_state): array {
    return $form;
  }

}
