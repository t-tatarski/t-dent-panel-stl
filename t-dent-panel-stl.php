<?php
/**
 * Plugin Name: T-Dent – Panel STL
 * Description: Prosty panel do przeglądania zleceń STL z Forminatora
 * Version: 1.0.0
 * Author: Tatarski
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// menu admin
add_action( 'admin_menu', function () {
    add_menu_page(
        'Zlecenia STL',
        'Zlecenia STL',
        'manage_options',
        't-dent-stl',
        't_dent_render_panel',
        'dashicons-clipboard',
        25
    );
});

//  panel
function t_dent_render_panel() {

    if ( ! class_exists( 'Forminator_API' ) ) {
        echo '<div class="notice notice-error"><p>Forminator musi być aktywny.</p></div>';
        return;
    }
    // 
    $form_id = 1; // Forminator form id 

    $entries = Forminator_API::get_entries( $form_id );

    echo '<div class="wrap">';
    echo '<h1>Zlecenia STL</h1>';

    if ( empty( $entries ) ) {
        echo '<p>Brak zleceń.</p>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>Data przyjęcia</th>
                <th>Dane pacjenta</th>
                <th>Typ pracy</th>
                <th>Materiał</th>
                <th>Termin sugerowany</th>
                <th>Plik ( STL )</th>
            </tr>
          </thead><tbody>';

    foreach ( $entries as $entry ) {

        $meta = $entry->meta_data;

        $patient  = $meta['textarea-1']['value'] ?? '-';
        $work     = $meta['radio-1']['value'] ?? '-';
        $date     = $meta['date-1']['value'] ?? '-';

        // checkbox → może być tablicą
        $material = '-';
        if ( isset( $meta['checkbox-1']['value'] ) ) {
            $material = is_array( $meta['checkbox-1']['value'] )
                ? implode( ', ', $meta['checkbox-1']['value'] )
                : $meta['checkbox-1']['value'];
        }

        // upload STL
        $file = $meta['upload-1']['value'][0]['file_url'] ?? '';

        echo '<tr>';
        echo '<td>' . esc_html( $entry->date_created ) . '</td>';
        echo '<td>' . esc_html( $patient ) . '</td>';
        echo '<td>' . esc_html( $work ) . '</td>';
        echo '<td>' . esc_html( $material ) . '</td>';
        echo '<td>' . esc_html( $date ) . '</td>';
        echo '<td>';

        if ( $file ) {
            echo '<a href="' . esc_url( $file ) . '" target="_blank">Pobierz STL</a>';
        } else {
            echo '-';
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
