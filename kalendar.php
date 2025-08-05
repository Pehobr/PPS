<?php
/**
 * Template Name: Kalendář
 * Description: Šablona pro zobrazení seznamu uplynulých týdnů.
 */

get_header();

// --- NASTAVENÍ ---
$apiKey = defined('GOOGLE_SHEETS_API_KEY') ? GOOGLE_SHEETS_API_KEY : '';
$spreadsheetId = defined('GOOGLE_SHEETS_SPREADSHEET_ID') ? GOOGLE_SHEETS_SPREADSHEET_ID : '';
// Načteme větší rozsah, abychom získali všechny týdny. Upravte, pokud máte více než ~140 týdnů dat.
$range = 'A2:A1000'; 
$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

// Funkce pro bezpečné zobrazení textu
function e_safe_cal($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// --- ZMĚNA: Získáme začátek aktuálního týdne pro porovnání ---
// Získáme dnešní datum a nastavíme čas na 00:00:00 pro přesné porovnání
$today = new DateTime('today');
// Najdeme pondělí aktuálního týdne
$current_week_start = (clone $today)->modify('monday this week');


// Načtení dat z Google Sheets
$apiUrl = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s', $spreadsheetId, $range, $apiKey);
$weeks = [];
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
        
        $current_week_dates = [];
        foreach ($values as $index => $row) {
            if (empty($row[0])) continue;

            try {
                $date_obj = new DateTime($row[0]);
                $day_of_week = $date_obj->format('N'); // 1 (pro Pondělí) až 7 (pro Neděli)

                if ($day_of_week == 1) { // Pokud je Pondělí, uložíme předchozí týden a začneme nový
                    if (!empty($current_week_dates)) {
                        $start_date = $current_week_dates[0];
                        $end_date = end($current_week_dates);

                        // --- ZMĚNA: Přidána podmínka pro zobrazení pouze minulých týdnů ---
                        if ($start_date < $current_week_start) {
                            $weeks[] = ['start_date' => $start_date, 'end_date' => $end_date];
                        }
                    }
                    $current_week_dates = []; // Resetujeme pro nový týden
                }
                
                $current_week_dates[] = $date_obj;

            } catch (Exception $e) {
                // Ignorovat neplatná data
                continue;
            }
        }
        // Přidáme poslední zpracovávaný týden (pokud splňuje podmínku)
        if (!empty($current_week_dates)) {
             $start_date = $current_week_dates[0];
             $end_date = end($current_week_dates);
             // --- ZMĚNA: Přidána podmínka i zde ---
             if ($start_date < $current_week_start) {
                $weeks[] = ['start_date' => $start_date, 'end_date' => $end_date];
             }
        }

        // Seřadíme týdny od nejnovějšího po nejstarší
        $weeks = array_reverse($weeks);

    } else {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe_cal($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Chyba při načítání dat z Google API (HTTP kód: ".e_safe_cal($http_code)."). Detail: ".$google_error;
    }
} else {
    $error_message = "Na serveru chybí cURL rozšíření pro PHP.";
}
?>

<link rel="stylesheet" id="kalendar-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/kalendar.css" type="text/css" media="all" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="calendar-container">
            <h1 class="calendar-title">Archiv týdnů</h1>
            <p class="calendar-description">Vyberte si týden, který si chcete zpětně projít.</p>

            <?php if (!empty($weeks)): ?>
                <div class="week-list">
                    <?php foreach ($weeks as $week): ?>
                        <?php
                            $start_date_formatted = $week['start_date']->format('j. n. Y');
                            $end_date_formatted = $week['end_date']->format('j. n. Y');
                            $link_date = $week['start_date']->format('Y-m-d'); // formát pro URL
                            $tyden_page_url = site_url('/tyden/'); // URL stránky s šablonou Týdenní přehled
                        ?>
                        <a href="<?php echo esc_url(add_query_arg('week', $link_date, $tyden_page_url)); ?>" class="week-button">
                            <?php echo e_safe_cal($start_date_formatted); ?> - <?php echo e_safe_cal($end_date_formatted); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php elseif(empty($error_message)): ?>
                 <p class="calendar-description">Momentálně zde nejsou žádné starší týdny k zobrazení.</p>
            <?php else: ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
?>
