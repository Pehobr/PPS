<?php
/**
 * Template Name: Formulář s otázkami
 * Description: Šablona pro zobrazení formuláře s otázkami ze soboty a odeslání odpovědí e-mailem.
 */

// --- Pomocné funkce ---
function e_safe_q($string) { return htmlspecialchars($string, ENT_QUOTES, 'UTF-8'); }
function get_czech_day_name_q($date_string) { if (empty($date_string)) return ''; $days_map = ['Monday'=>'Pondělí', 'Tuesday'=>'Úterý', 'Wednesday'=>'Středa', 'Thursday'=>'Čtvrtek', 'Friday'=>'Pátek', 'Saturday'=>'Sobota', 'Sunday'=>'Neděle']; try { $date_obj = new DateTime($date_string); return $days_map[$date_obj->format('l')] ?? ''; } catch (Exception $e) { return ''; } }

// --- KROK 1: NAČTENÍ DAT A ZJIŠTĚNÍ STAVU LOSOVÁNÍ ---
$apiKey = defined('GOOGLE_SHEETS_API_KEY') ? GOOGLE_SHEETS_API_KEY : '';
$spreadsheetId = defined('GOOGLE_SHEETS_SPREADSHEET_ID') ? GOOGLE_SHEETS_SPREADSHEET_ID : '';
$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$saturday_data = null;
$error_message = '';
$week_identifier = 'Sobotní otázky';
$target_week_monday = null;
$losovani_active = false;
$range_to_fetch = 'A2:H1000'; // Vždy načítáme větší rozsah

// Zpracování URL parametru 'week'
if (isset($_GET['week']) && $_GET['week'] !== 'current') {
    $week_param = sanitize_text_field($_GET['week']);
    try {
        $date_obj = new DateTime($week_param);
        if ($date_obj && $date_obj->format('Y-m-d') === $week_param && $date_obj->format('N') == 1) {
            $target_week_monday = $date_obj;
        }
    } catch (Exception $e) { $target_week_monday = null; }
}

// Načítání dat z Google Sheets
$apiUrl = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s', $spreadsheetId, $range_to_fetch, $apiKey);
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $apiUrl, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Referer: ' . $current_domain]]);
    $json_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $json_data) {
        $data = json_decode($json_data, true);
        $all_rows = $data['values'] ?? [];

        $target_saturday_date = null;
        if ($target_week_monday) {
            // Hledáme sobotu v ARCHIVNÍM týdnu
            $target_saturday_date = (clone $target_week_monday)->modify('saturday this week');
        } else {
            // --- OPRAVA ZDE: Hledáme sobotu v AKTUÁLNÍM týdnu ---
            $today = new DateTime('today');
            $target_saturday_date = (clone $today)->modify('saturday this week');
        }

        // Najdeme řádek s datem cílové soboty
        foreach ($all_rows as $row) {
            if (empty($row[0])) continue;
            try {
                $row_date_obj = new DateTime($row[0]);
                if ($row_date_obj->format('Y-m-d') === $target_saturday_date->format('Y-m-d')) {
                    $saturday_data = $row;
                    break;
                }
            } catch (Exception $e) { continue; }
        }

        if ($saturday_data) {
            $losovani_active = (isset($saturday_data[6]) && strtolower(trim($saturday_data[6])) === 'ano');
            try {
                $saturday_date_obj = new DateTime($saturday_data[0]);
                $monday_obj = (clone $saturday_date_obj)->modify('last monday');
                $sunday_obj = (clone $saturday_date_obj)->modify('next sunday');
                $week_identifier = 'Otázky pro týden: ' . $monday_obj->format('j. n. Y') . ' - ' . $sunday_obj->format('j. n. Y');
            } catch (Exception $e) { /* Ponechá se výchozí */ }
        } else {
            if (empty($error_message)) $error_message = 'Data pro sobotu v požadovaném týdnu nebyla nalezena.';
        }
    } else {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe_q($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Chyba při načítání dat z Google API (HTTP kód: ".e_safe_q($http_code)."). Detail: ".$google_error;
    }
} else {
    $error_message = "Na serveru chybí cURL rozšíření pro PHP.";
}


// --- KROK 2: ZPRACOVÁNÍ FORMULÁŘE ---
$form_sent = false;
$form_error = '';
if ($losovani_active && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_questions'])) {
    if (!isset($_POST['question_form_nonce']) || !wp_verify_nonce($_POST['question_form_nonce'], 'submit_questions_action')) {
        $form_error = 'Chyba zabezpečení. Zkuste to prosím znovu.';
    } else {
        $jmeno_prijmeni = sanitize_text_field($_POST['jmeno_prijmeni']);
        $email_address = sanitize_email($_POST['email_address']);
        $odpovedi = $_POST['odpovedi'] ?? [];
        if (empty($email_address) || !filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'Prosím, zadejte platnou e-mailovou adresu. Je to povinný údaj.';
        } else {
            $email_body = "Nové odpovědi pro týden: " . sanitize_text_field($_POST['week_identifier_hidden']) . "\n\n";
            $email_body .= "E-mail odesílatele (pro měsíční slosování): " . $email_address . "\n";
            if (!empty($jmeno_prijmeni)) $email_body .= "Jméno a příjmení: " . $jmeno_prijmeni . "\n";
            $email_body .= "----------------------------------------\n\n";
            foreach ($odpovedi as $index => $odpoved) {
                $cislo_otazky = $index + 1;
                $otazka = sanitize_text_field($_POST['otazky'][$index]);
                $vycistena_odpoved = sanitize_textarea_field($odpoved);
                $email_body .= $cislo_otazky . ". otázka: " . $otazka . "\n";
                $email_body .= $cislo_otazky . ". odpověď: " . (!empty($vycistena_odpoved) ? $vycistena_odpoved : "-") . "\n\n";
            }
            $to = get_option('admin_email');
            $subject = 'Nové odpovědi - ' . sanitize_text_field($_POST['week_identifier_hidden']);
            $headers = ['Content-Type: text/plain; charset=UTF-8'];
            if (wp_mail($to, $subject, $email_body, $headers)) {
                $form_sent = true;
            } else {
                $form_error = 'E-mail se nepodařilo odeslat. Zkuste to prosím později.';
            }
        }
    }
}

// --- KROK 3: ZOBRAZENÍ STRÁNKY ---
get_header();
?>

<link rel="stylesheet" id="otazky-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/otazky.css" type="text/css" media="all" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="question-form-container">
            <h1><?php echo e_safe_q($week_identifier); ?></h1>
            
            <?php if ($form_sent): ?>
                <div class="form-success-message">Děkujeme za odeslání odpovědí! Byli jste zařazeni do měsíčního slosování.</div>
                <?php if ($target_week_monday): ?>
                    <div style="text-align:center; margin-top: 20px;"><a href="<?php echo esc_url(add_query_arg('week', $target_week_monday->format('Y-m-d'), site_url('/tyden/'))); ?>" style="font-size: 1.1em;">&larr; Zpět na přehled týdne</a></div>
                <?php endif; ?>
            <?php elseif (!empty($form_error)): ?>
                <div class="form-error-message"><?php echo $form_error; ?></div>
            <?php endif; ?>

            <?php if ($saturday_data && !$form_sent): ?>
                <?php if ($losovani_active): ?>
                    <?php
                        $questions_text = $saturday_data[3] ?? '';
                        $questions = preg_split('/\r\n|\r|\n/', $questions_text);
                        $questions = array_filter(array_map('trim', $questions));
                    ?>
                    <?php if (!empty($questions)): ?>
                        <p class="form-description">Odpovězte na otázky a zařaďte se do měsíčního slosování o cenu.</p>
                        <form action="" method="post" class="questions-form">
                            <?php wp_nonce_field('submit_questions_action', 'question_form_nonce'); ?>
                            <input type="hidden" name="week_identifier_hidden" value="<?php echo e_safe_q($week_identifier); ?>">
                            <div class="form-group"><label for="jmeno_prijmeni">Jméno a příjmení (nepovinné)</label><input type="text" id="jmeno_prijmeni" name="jmeno_prijmeni"></div>
                            <div class="form-group"><label for="email_address">E-mail <span class="required">*</span></label><input type="email" id="email_address" name="email_address" required></div>
                            <?php foreach ($questions as $index => $question): ?>
                                <div class="form-group">
                                    <label for="odpoved_<?php echo $index; ?>"><?php echo e_safe_q($question); ?></label>
                                    <input type="hidden" name="otazky[<?php echo $index; ?>]" value="<?php echo e_safe_q($question); ?>">
                                    <textarea id="odpoved_<?php echo $index; ?>" name="odpovedi[<?php echo $index; ?>]" rows="4"></textarea>
                                </div>
                            <?php endforeach; ?>
                            <div class="form-submit"><button type="submit" name="submit_questions">Odeslat a zařadit do měsíčního slosování</button></div>
                        </form>
                    <?php else: ?>
                        <p class="error-message">V tabulce nebyly pro sobotu nalezeny žádné otázky.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="form-description">Omlouváme se, ale možnost odesílat odpovědi pro tento týden již byla uzavřena.</p>
                <?php endif; ?>
            <?php elseif ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php
?>
