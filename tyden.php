<?php
/**
 * Template Name: Týdenní přehled
 * Description: Šablona pro zobrazení biblického přehledu z Google Sheets.
 */

get_header();

// --- NASTAVENÍ ---
$apiKey = 'AIzaSyDAmhStJ2lEeZG4qiqEpb92YrShfaDY6DE'; // <-- DOPLŇTE VÁŠ SKUTEČNÝ API KLÍČ
$spreadsheetId = '1ZbaVVX2tJj7kWYczWopJMNZ7oU8YtXcGwp2EKgZ7XQo'; // <-- DOPLŇTE VAŠE SKUTEČNÉ ID TABULKY
$range = 'A2:F8'; // Rozsah dat, které chcete načíst

// --- AUTOMATICKÁ DETEKCE DOMÉNY ---
// Tento kód automaticky zjistí doménu, na které stránka běží.
// Nemusíte ho měnit, bude fungovat na .local i na ostré doméně.
$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Funkce pro bezpečné zobrazení textu
function e_safe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Sestavení URL adresy pro Google Sheets API
$apiUrl = sprintf(
    'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s',
    $spreadsheetId,
    $range,
    $apiKey
);

$values = [];
$error_message = '';

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    // Použijeme automaticky zjištěnou doménu pro Referer
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Referer: ' . $current_domain]);

    $json_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($json_data === false) {
        $error_message = "Chyba při komunikaci se serverem (cURL).";
    } elseif ($http_code !== 200) {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Server Google API vrátil chybu (HTTP kód: " . e_safe($http_code) . "). Detail: " . $google_error;
    } else {
        $data = json_decode($json_data, true);
        if (isset($data['values'])) {
            $values = $data['values'];
        } else {
            $error_message = "Data se podařilo načíst, ale mají nesprávný formát.";
        }
    }
} else {
    $error_message = "Na serveru není povolena funkce cURL. Kontaktujte prosím administrátora hostingu.";
}
?>

<link rel="stylesheet" id="tyden-css" href="<?php echo get_template_directory_uri(); ?>/css/tyden.css" type="text/css" media="all" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="container-tyden">
            <div class="weekly-overview">
                <?php if (!empty($values)): ?>
                    <?php foreach ($values as $row): ?>
                        <?php
                        if (empty(array_filter($row))) continue;
                        
                        $isReading = (isset($row[1]) && in_array(strtolower(trim($row[1])), ['čtení', 'cteni']));
                        $dayTypeClass = $isReading ? 'day-reading' : 'day-reflection';
                        $date = $row[0] ?? '';
                        $title = $row[2] ?? 'Bez titulku';
                        $content = $row[3] ?? 'Obsah není k dispozici.';
                        $audio_url = $row[5] ?? '';
                        ?>
                        <article class="day-card <?php echo $dayTypeClass; ?>">
                            <div class="day-header">
                                <span class="date"><?php echo e_safe($date); ?></span>
                                <h2><?php echo e_safe($title); ?></h2>
                            </div>
                            <div class="day-content">
                                <p><?php echo nl2br(e_safe($content)); ?></p>
                            </div>
                            <?php if ($isReading && !empty($audio_url)): ?>
                                <div class="day-audio">
                                    <p class="audio-title">Poslechnout audio:</p>
                                    <audio controls src="<?php echo esc_url($audio_url); ?>">Váš prohlížeč nepodporuje audio.</audio>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="error-message">
                        <?php echo $error_message ? $error_message : 'Nebylo možné načíst data pro tento týden.'; ?>
                    </p>
                <?php endif; ?>
            </div>
            <footer class="footer-tyden">
                <p>Vytvořeno s láskou k Božímu slovu.</p>
            </footer>
        </div>
    </main>
</div>

<script src="<?php echo get_template_directory_uri(); ?>/js/tyden.js"></script>

<?php
get_footer();
?>