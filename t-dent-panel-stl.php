<?php
/**
 * Plugin Name: T-Dent – Panel STL
 * Description: Prosty panel do przeglądania zleceń STL z Forminatora
 * Version: 1.0.0
 * Author: Tatarski
 * Plugin URI: https://github.com/t-tatarski/t-dent-panel-stl.git
 * Licence: MIT
 */


if ( ! defined( 'ABSPATH' ) ) exit;

// Dodanie pozycji w menu admina
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

// Render panelu
function t_dent_render_panel() {

 echo '<div class="notice notice-error"><p>Ustaw w kodzie numeryczne ID formularza z Forminatora (linijka 37)</p></div>';

    if ( ! class_exists( 'Forminator_API' ) ) {
        echo '<div class="notice notice-error"><p>Forminator nie jest aktywny.</p></div>';
        return;
    }
//  WAZNE! 
    $form_id = 61; // <- ustaw dokładne ID formularza

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
                <th>Data systemowa</th>
                <th>IP zgłaszającego</th>
                <th>Dane pacjenta</th>
                <th>Typ pracy</th>
                <th>Materiał</th>
                <th>Termin</th>
                <th>Plik STL</th>
            </tr>
          </thead><tbody>';

    foreach ( $entries as $entry ) {

        $meta = $entry->meta_data;

        $system_date = $meta['hidden-1']['value'] ?? '-';
        $ip          = $meta['_forminator_user_ip']['value'] ?? '-';
        $patient     = $meta['textarea-1']['value'] ?? '-';
        $work        = $meta['radio-1']['value'] ?? '-';
        $material    = $meta['checkbox-1']['value'] ?? '-';
        $date        = $meta['date-1']['value'] ?? '-';

        // Upload STL
        $file = '';
        if ( isset( $meta['upload-1']['value']['file_url'][0] ) ) {
            $file = $meta['upload-1']['value']['file_url'][0];
        }

        echo '<tr>';
        echo '<td>' . esc_html( $system_date ) . '</td>';
        echo '<td>' . esc_html( $ip ) . '</td>';
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
