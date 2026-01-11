<?php
/**
 * Plugin Name: T-Dent – Panel STL
 * Description: Prosty panel do przeglądania zleceń STL z Forminatora
 * Version: 1.1.0
 * Author: Tatarski
 * Plugin URI: https://github.com/t-tatarski/t-dent-panel-stl.git
 * Licence: MIT
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// admin menu
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

// Funkcja renderująca panel
function t_dent_render_panel() {

    echo '<div class="notice notice-error"><p>Ustaw w kodzie numeryczne ID formularza z Forminatora (linijka 36)</p></div>';

    if ( ! class_exists( 'Forminator_API' ) ) {
        echo '<div class="notice notice-error"><p>Forminator nie jest aktywny.</p></div>';
        return;
    }

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
        $file_path = '';
        if ( isset( $meta['upload-1']['value']['file_path'][0] ) ) {
            $file_path = $meta['upload-1']['value']['file_path'][0];
        }

        echo '<tr>';
        echo '<td>' . esc_html( $system_date ) . '</td>';
        echo '<td>' . esc_html( $ip ) . '</td>';
        echo '<td>' . esc_html( $patient ) . '</td>';
        echo '<td>' . esc_html( $work ) . '</td>';
        echo '<td>' . esc_html( $material ) . '</td>';
        echo '<td>' . esc_html( $date ) . '</td>';
        echo '<td>';

        if ( $file_path && file_exists($file_path) ) {
            // Tworzymy bezpieczny link do pobrania przez admin_post
            $download_url = admin_url( 'admin-post.php?action=download_stl&file=' . urlencode($file_path) );
            echo '<a href="' . esc_url($download_url) . '" target="_blank">Pobierz STL</a>';
        } else {
            echo '-';
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

// Obsługa pobierania plików STL
add_action('admin_post_download_stl', function() {
    if ( !current_user_can('manage_options') || !isset($_GET['file']) ) {
        wp_die('Brak dostępu');
    }

    $file = $_GET['file'];

    // Bezpieczna walidacja pliku
    $allowed_dir = wp_upload_dir()['basedir']; // katalog uploads WordPress
    $realpath = realpath($file);
    if ( !$realpath || strpos($realpath, $allowed_dir) !== 0 || !file_exists($realpath) ) {
        wp_die('Plik nie istnieje lub brak dostępu');
    }

    // Wysyłamy plik do przeglądarki
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($realpath) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($realpath));
    readfile($realpath);
    exit;
});
