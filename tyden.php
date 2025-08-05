<?php
/**
 * Template Name: Týdenní přehled
 * Description: Šablona pro zobrazení biblického přehledu z Google Sheets.
 */

get_header();

// --- NASTAVENí ---
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
    $days_map = ['Monday'=>'Pondělí', 'Tuesday'=>'Úterý', 'Wednesday'=>'Středa', 'Thursday'=>'Čtvrtek', 'Friday'=>'Pátek', 'Saturday'=>'Sobota', 'Sunday'=>'Neděle'];
    try { $date_obj = new DateTime($date_string); return $days_map[$date_obj->format('l')] ?? ''; } catch (Exception $e) { return ''; }
}

// Funkce pro formátování data do českého formátu
function format_czech_date($date_string) {
    if (empty($date_string)) return '';
    try { $date_obj = new DateTime($date_string); return $date_obj->format('j.n.Y'); } catch (Exception $e) { return $date_string; }
}

// Kód pro načtení dat z Google Sheets
$apiUrl = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s', $spreadsheetId, $range, $apiKey);
$values = [];
$error_message = '';
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $apiUrl, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Referer: ' . $current_domain]]);
    $json_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($http_code == 200 && $json_data) {
        $data = json_decode($json_data, true);
        $values = $data['values'] ?? [];
    } else {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Chyba při načítání dat z Google API (HTTP kód: ".e_safe($http_code)."). Detail: ".$google_error;
    }
} else {
    $error_message = "Na serveru chybí cURL rozšíření pro PHP.";
}
?>

<link rel="stylesheet" id="tyden-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/tyden.css" type="text/css" media="all" />
<link rel="stylesheet" id="audio-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/audio.css" type="text/css" media="all" />
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
                    $content = $row[3] ?? '';
                    $audio_evangelista = $row[4] ?? '';
                    $audio_kapitola_vers = $row[5] ?? '';
                    $has_audio = !empty($audio_evangelista) && !empty($audio_kapitola_vers);
                    ?>
                    <div class="accordion-item">
                        <h3 class="day-heading"><?php echo e_safe($day_name_cz . ($formatted_date ? ' - ' . $formatted_date : '')); ?></h3>
                        <?php
                        // OPRAVA ZDE: Nyní se řídíme názvem dne, ne titulkem.
                        $button_class = 'accordion-button';
                        if ($day_name_cz === 'Sobota') {
                            $button_class .= ' saturday-btn';
                        } elseif ($day_name_cz === 'Neděle') {
                            $button_class .= ' sunday-btn';
                        }
                        ?>
                        <button type="button" class="<?php echo $button_class; ?>">
                            <?php echo e_safe($title); ?>
                        </button>
                        <div class="accordion-content">
                            <div class="content-inner">
                                <?php if ($has_audio): 
                                    $full_audio_url = 'https://audiokostel.cz/pps/' . strtolower(trim($audio_evangelista)) . '-' . trim($audio_kapitola_vers) . '.mp3';
                                ?>
                                    <div class="custom-audio-player-wrapper">
                                        <div class="custom-audio-player">
                                            <button class="play-pause-btn paused"><i class="fas fa-play"></i><i class="fas fa-pause"></i></button>
                                            <div class="progress-bar-container"><div class="progress-bar-fill"></div></div>
                                            <div class="volume-container"><i class="fas fa-volume-up volume-icon"></i><input type="range" class="volume-slider" min="0" max="1" step="0.05" value="1"></div>
                                        </div>
                                        <audio src="<?php echo esc_url($full_audio_url); ?>" preload="metadata" style="display: none;"></audio>
                                    </div>
                                <?php endif; ?>

                                <?php
                                $lines = preg_split('/\r\n|\r|\n/', $content);
                                $cleaned_lines = [];
                                foreach ($lines as $line) {
                                    if (trim($line) !== '') {
                                        $cleaned_lines[] = e_safe(trim($line));
                                    }
                                }
                                $final_content = implode('<br />', $cleaned_lines);
                                ?>
                                <p><?php echo $final_content; ?></p>
                                <?php if ($day_name_cz === 'Sobota'): ?>
    <div class="form-link-wrapper">
        <a href="<?php echo site_url('/otazky/'); ?>" class="link-to-form-btn">Odeslat odpovědi</a>
    </div>
<?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="error-message"><?php echo $error_message ? $error_message : 'Nebylo možné načíst data.'; ?></p>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="<?php echo get_stylesheet_directory_uri(); ?>/js/tyden.js"></script>

<?php
get_footer();
?>