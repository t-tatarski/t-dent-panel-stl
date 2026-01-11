
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
    if ( ! class_exists( 'Forminator_API' ) ) {
        echo '<div class="wrap"><div class="notice notice-error"><p>Forminator nie jest aktywny.</p></div></div>';
        return;
    }

    $form_id = 61; // <- USTAW SWÓJ NUMERYCZNY ID FORMULARZA Z FORMINATORA

    $entries = Forminator_API::get_entries( $form_id );

    echo '<div class="wrap">';
    echo '<h1>Zlecenia STL <small>(Formularz ID: ' . esc_html($form_id) . ')</small></h1>';

    if ( empty( $entries ) ) {
        echo '<p>Brak zleceń dla tego formularza.</p>';
        echo '</div>';
        return;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>ID</th>
                <th>Data systemowa</th>
                <th>IP</th>
                <th>Dane pacjenta</th>
                <th>Typ pracy</th>
                <th>Materiał</th>
                <th>Termin</th>
                <th>Plik STL</th>
            </tr>
          </thead><tbody>';

    foreach ( $entries as $entry ) {
        $meta = $entry->meta_data;

        $entry_id     = $entry->entry_id ?? '-';
        $system_date  = $meta['hidden-1']['value'] ?? '-';
        $ip           = $meta['_forminator_user_ip']['value'] ?? '-';
        $patient      = $meta['textarea-1']['value'] ?? '-';
        $work         = $meta['radio-1']['value'] ?? '-';
        $material     = $meta['checkbox-1']['value'] ?? (is_array($meta['checkbox-1']['value'] ?? []) ? implode(', ', $meta['checkbox-1']['value']) : '-');
        $date         = $meta['date-1']['value'] ?? '-';

        // Poprawiona obsługa pliku STL z Forminatora
        $file_path = '';
        $file_name = '';
        
        if ( isset( $meta['upload-1']['value']['file'] ) && is_array( $meta['upload-1']['value']['file'] ) ) {
            $file_info = $meta['upload-1']['value']['file'][0] ?? null;
            if ( $file_info ) {
                $file_path = $file_info['file_path'] ?? '';
                $file_name = $file_info['file_name'] ?? basename($file_path);
            }
        } elseif ( isset( $meta['upload-1']['value']['file_path'][0] ) ) {
            $file_path = $meta['upload-1']['value']['file_path'][0];
            $file_name = basename($file_path);
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

        if ( $file_path && file_exists( ABSPATH . $file_path ) ) {
            // Bezpieczne kodowanie + nonce
            $file_path_encoded = base64_encode( ABSPATH . $file_path );
            $nonce = wp_create_nonce( 'download_stl_' . $file_path_encoded );
            $download_url = admin_url( 'admin-post.php?action=download_stl&file=' . $file_path_encoded . '&_wpnonce=' . $nonce . '&name=' . urlencode($file_name) );
            
            echo '<a href="' . esc_url( $download_url ) . '" target="_blank" class="button button-small">📥 ' . esc_html( $file_name ) . '</a>';
        } else {
            echo '<span class="notice notice-warning inline">Brak pliku lub usunięty</span>';
        }

        echo '</td></tr>';
    }

    echo '</tbody></table>';
    echo '<p><small><strong>Uwaga:</strong> Sprawdź ID pól w Forminator > Entries jeśli dane się nie wyświetlają (upload-1, textarea-1 itp.)</small></p>';
    echo '</div>';
}

// Zabezpieczona obsługa pobierania plików STL
add_action( 'admin_post_download_stl', 't_dent_download_stl' );

function t_dent_download_stl() {
    // Tylko dla admina w panelu
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Brak dostępu do panelu admina.' );
    }

    $file_encoded = sanitize_text_field( $_GET['file'] ?? '' );
    $nonce = sanitize_text_field( $_GET['_wpnonce'] ?? '' );
    $file_name = sanitize_file_name( urldecode( $_GET['name'] ?? 'file.stl' ) );

    // Sprawdzenie nonce
    if ( ! wp_verify_nonce( $nonce, 'download_stl_' . $file_encoded ) ) {
        wp_die( 'Niewłaściwy klucz bezpieczeństwa.' );
    }

    // Dekodowanie ścieżki
    $file_path = base64_decode( $file_encoded );
    
    // Walidacja ścieżki - tylko katalogi uploads Forminatora
    $uploads = wp_upload_dir()['basedir'];
    $forminator_uploads = $uploads . '/forminator/';
    
    if ( strpos( $file_path, $uploads ) !== 0 || 
         strpos( $file_path, '/forminator/' ) === false ||
         ! file_exists( $file_path ) ||
         strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) ) !== 'stl' ) {
        wp_die( 'Nieprawidłowy plik STL lub brak dostępu.' );
    }

    // Nagłówki do pobrania
    nocache_headers();
    header( 'Content-Type: application/octet-stream' );
    header( 'Content-Disposition: attachment; filename="' . $file_name . '"' );
    header( 'Content-Length: ' . filesize( $file_path ) );
    header( 'X-Accel-Buffering: no' ); // Nginx

    readfile( $file_path );
    exit;
}

// Bonus: Dodaj MIME type dla STL (jeśli brak)
add_filter( 'upload_mimes', function( $mime_types ) {
    $mime_types['stl'] = 'model/stl';
    return $mime_types;
});
