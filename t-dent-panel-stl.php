<?php
/**
 * Plugin Name: T-Dent – Panel STL
 * Description: Panel do pobierania STL z Forminatora WEBD
 * Version: 1.7.0
 * Author: Tatarski
 * Licence: MIT
 * Plugin URI: https://github.com/t-tatarski/t-dent-panel-stl/blob/main/t-dent-panel-stl.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
    add_menu_page( 'Zlecenia STL', 'Zlecenia STL', 'manage_options', 't-dent-stl', 't_dent_render_panel', 'dashicons-clipboard', 25 );
});

function t_dent_render_panel() {
    if ( ! class_exists( 'Forminator_API' ) ) {
        echo '<div class="wrap"><div class="notice notice-info"><p>plugin wymaga wpisania w kodzie właściwego form ID z forminatora. (linia 24)</p></div></div>';
        echo '<div class="wrap"><div class="notice notice-error"><p>Forminator nie aktywny.</p></div></div>';
        return;
    }

    $form_id = 61; // TWOJE ID formularza
    $entries = Forminator_API::get_entries( $form_id );

    echo '<div class="wrap">';
    echo '<h1>Zlecenia STL <small>(Formularz #'. $form_id .')</small></h1>';

    if ( empty( $entries ) ) {
        echo '<p>Brak zleceń.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>IP</th>
                <th>Pacjent</th>
                <th>Typ pracy</th>
                <th>Materiał</th>
                <th>Termin</th>
                <th>Plik STL</th>
            </tr>
          </thead><tbody>';

    foreach ( $entries as $entry ) {
        $meta = $entry->meta_data;
        $entry_id = $entry->entry_id ?? '-';

        // Pola formularza
        $system_date = $meta['hidden-1']['value'] ?? '-';
        $ip = $meta['_forminator_user_ip']['value'] ?? '-';
        $patient = $meta['textarea-1']['value'] ?? '-';
        $work = $meta['radio-1']['value'] ?? '-';
        $material = is_array($meta['checkbox-1']['value'] ?? []) ? implode(', ', $meta['checkbox-1']['value']) : ($meta['checkbox-1']['value'] ?? '-');
        $date = $meta['date-1']['value'] ?? '-';

        $file_path = '';
        $file_name = '';
        
        if ( isset( $meta['upload-1']['value']['file']['file_path'][0] ) ) {
            $file_path = $meta['upload-1']['value']['file']['file_path'][0];
            $file_url = $meta['upload-1']['value']['file']['file_url'][0] ?? '';
            $file_name = basename( $file_path );
        }

        echo '<tr>';
        echo '<td><strong>' . esc_html( $entry_id ) . '</strong></td>';
        echo '<td>' . esc_html( $system_date ) . '</td>';
        echo '<td>' . esc_html( $ip ) . '</td>';
        echo '<td>' . esc_html( $patient ) . '</td>';
        echo '<td>' . esc_html( $work ) . '</td>';
        echo '<td>' . esc_html( $material ) . '</td>';
        echo '<td>' . esc_html( $date ) . '</td>';
        echo '<td>';

        if ( $file_path && file_exists( $file_path ) ) {
            // download stl
            $file_encoded = base64_encode( $file_path );
            $nonce = wp_create_nonce( 'download_stl_' . $file_encoded );
            $download_url = admin_url( 'admin-post.php?action=download_stl&file=' . $file_encoded . '&_wpnonce=' . $nonce . '&name=' . urlencode( $file_name ) );
            
            echo '<a href="' . esc_url( $download_url ) . '" target="_blank" class="button button-small">📥 ' . esc_html( $file_name ) . '</a>';
        } else {
            echo '<span class="notice notice-warning inline">Plik usunięty</span>';
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}

// download funkcja
add_action( 'admin_post_download_stl', 't_dent_download_stl' );
function t_dent_download_stl() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Brak dostępu' );
    }

    $file_encoded = sanitize_text_field( $_GET['file'] ?? '' );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] ?? '' );
    $file_name = urldecode( sanitize_text_field( $_GET['name'] ?? '' ) );

    if ( ! wp_verify_nonce( $nonce, 'download_stl_' . $file_encoded ) ) {
        wp_die( 'Błąd bezpieczeństwa' );
    }

    $file_path = base64_decode( $file_encoded );
    
    // Walidacja 
    if ( strpos( $file_path, '/uploads/forminator/' ) === false || 
         ! file_exists( $file_path ) || 
         pathinfo( $file_path, PATHINFO_EXTENSION ) !== 'stl' ) {
        wp_die( 'Nieprawidłowy plik STL' );
    }

    
    nocache_headers();
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $file_path ) ) . '"' );
    header( 'Content-Length: ' . filesize( $file_path ) );

    readfile( $file_path );
    exit;
}

// MIME type STL
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['stl'] = 'model/stl';
    return $mimes;
});
