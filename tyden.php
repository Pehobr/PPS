<?php
/**
 * Template Name: Týdenní přehled
 * Description: Šablona pro zobrazení biblického přehledu z Google Sheets.
 */

get_header();

// --- NASTAVENÍ ---
$apiKey = 'AIzaSyDAmhStJ2lEeZG4qiqEpb92YrShfaDY6DE'; // Váš API klíč
$spreadsheetId = '1ZbaVVX2tJj7kWYczWopJMNZ7oU8YtXcGwp2EKgZ7XQo'; // Vaše ID tabulky
$range = 'A2:G8';
$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Funkce pro bezpečné zobrazení textu
function e_safe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Mapa pro převod anglických názvů dnů na české
function get_czech_day_name($date_string) {
    if (empty($date_string)) return '';
    $days_map = [
        'Monday'    => 'Pondělí',
        'Tuesday'   => 'Úterý',
        'Wednesday' => 'Středa',
        'Thursday'  => 'Čtvrtek',
        'Friday'    => 'Pátek',
        'Saturday'  => 'Sobota',
        'Sunday'    => 'Neděle'
    ];
    try {
        $date_obj = new DateTime($date_string);
        $day_english = $date_obj->format('l');
        return $days_map[$day_english] ?? '';
    } catch (Exception $e) {
        return '';
    }
}

// Funkce pro formátování data do českého formátu
function format_czech_date($date_string) {
    if (empty($date_string)) return '';
    try {
        $date_obj = new DateTime($date_string);
        return $date_obj->format('j.n.Y');
    } catch (Exception $e) {
        return $date_string;
    }
}

// Kód pro načtení dat z Google Sheets
$apiUrl = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s', $spreadsheetId, $range, $apiKey);
$values = [];
$error_message = '';
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: ' . $current_domain]);
    $json_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code == 200 && $json_data) {
        $data = json_decode($json_data, true);
        $values = $data['values'] ?? [];
    } else {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Chyba při načítání dat z Google API (HTTP kód: ".e_safe($http_code)."). Zkontrolujte nastavení sdílení tabulky a omezení API klíče. Detail od Googlu: ".$google_error;
    }
} else {
    $error_message = "Na serveru chybí cURL rozšíření pro PHP.";
}
?>

<link rel="stylesheet" id="tyden-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/tyden.css" type="text/css" media="all" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="accordion-container">
            <?php if (!empty($values)): ?>
                <?php foreach ($values as $row): ?>
                    <?php
                    if (empty(array_filter($row))) continue;
                    
                    $date = $row[0] ?? '';
                    $day_name_cz = get_czech_day_name($date);
                    $formatted_date = format_czech_date($date);
                    $title = $row[2] ?? 'Bez titulku';
                    $content = $row[3] ?? 'Obsah není k dispozici.';
                    $audio_evangelista = $row[4] ?? '';
                    $audio_kapitola_vers = $row[5] ?? '';
                    $has_audio = !empty($audio_evangelista) && !empty($audio_kapitola_vers);
                    ?>
                    <div class="accordion-item">
                        <h3 class="day-heading"><?php echo e_safe($day_name_cz . ($formatted_date ? ' - ' . $formatted_date : '')); ?></h3>
                        <button type="button" class="accordion-button">
                            <?php echo e_safe($title); ?>
                        </button>
                        <div class="accordion-content">
                            <div class="content-inner">
                                
                                <?php // ZMĚNA ZDE: Audio přehrávač je nyní jako první
                                if ($has_audio): 
                                    $evangelista_slug = strtolower(trim($audio_evangelista));
                                    $kapitola_slug = trim($audio_kapitola_vers);
                                    $full_audio_url = 'https://audiokostel.cz/pps/' . $evangelista_slug . '-' . $kapitola_slug . '.mp3';
                                ?>
                                    <div class="day-audio">
                                        <p class="audio-title">Poslechnout audio:</p>
                                        <audio controls src="<?php echo esc_url($full_audio_url); ?>">Váš prohlížeč nepodporuje audio.</audio>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Odstranění prázdných řádků z obsahu
                                $cleaned_content = preg_replace('/(\r\n|\r|\n){2,}/', "\n", $content);
                                ?>
                                <p><?php echo nl2br(e_safe($cleaned_content)); ?></p>
                                
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error-message">
                    <?php echo $error_message ? $error_message : 'Nebylo možné načíst data pro tento týden.'; ?>
                </p>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/tyden.js"></script>

<?php
get_footer();
?>