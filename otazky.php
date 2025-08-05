<?php
/**
 * Template Name: Formulář s otázkami
 * Description: Šablona pro zobrazení formuláře s otázkami ze soboty a odeslání odpovědí e-mailem.
 */

// --- ZPRACOVÁNÍ FORMULÁŘE (UPRAVENO) ---
$form_sent = false;
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_questions'])) {
    // Bezpečnostní kontrola (nonce)
    if (!isset($_POST['question_form_nonce']) || !wp_verify_nonce($_POST['question_form_nonce'], 'submit_questions_action')) {
        $form_error = 'Chyba zabezpečení. Zkuste to prosím znovu.';
    } else {
        // Načtení a vyčištění dat z formuláře
        $jmeno_prijmeni = sanitize_text_field($_POST['jmeno_prijmeni']);
        $email_address = sanitize_email($_POST['email_address']); // <-- ZMĚNA: Nové pole pro email
        $odpovedi = $_POST['odpovedi'] ?? [];

        // <-- ZMĚNA: Kontrola povinného e-mailu místo jména
        if (empty($email_address)) {
            $form_error = 'Prosím, vyplňte váš e-mail. Je to povinný údaj pro zařazení do slosování.';
        } elseif (!filter_var($email_address, FILTER_VALIDATE_EMAIL)) {
            $form_error = 'Prosím, zadejte platnou e-mailovou adresu.';
        } else {
            // Sestavení těla e-mailu
            $email_body = "Nové odpovědi z webového formuláře:\n\n";
            $email_body .= "E-mail odesílatele (pro slosování): " . $email_address . "\n"; // <-- ZMĚNA: Přidán e-mail
            
            // <-- ZMĚNA: Jméno se přidá, jen pokud je vyplněné
            if (!empty($jmeno_prijmeni)) {
                $email_body .= "Jméno a příjmení: " . $jmeno_prijmeni . "\n";
            }
            
            $email_body .= "----------------------------------------\n\n";

            foreach ($odpovedi as $index => $odpoved) {
                $otazka = sanitize_text_field($_POST['otazky'][$index]);
                $vycistena_odpoved = sanitize_textarea_field($odpoved);
                $email_body .= "Otázka: " . $otazka . "\n";
                $email_body .= "Odpověď: " . $vycistena_odpoved . "\n\n";
            }

            // Nastavení pro e-mail
            $to = get_option('admin_email');
            $subject = 'Nové odpovědi na sobotní otázky';
            $headers = ['Content-Type: text/plain; charset=UTF-8'];

            // Odeslání e-mailu přes WordPress
            if (wp_mail($to, $subject, $email_body, $headers)) {
                $form_sent = true;
            } else {
                $form_error = 'E-mail se nepodařilo odeslat. Zkuste to prosím později.';
            }
        }
    }
}


// --- NAČTENÍ DAT Z GOOGLE SHEETS (zůstává stejné) ---
get_header();

$apiKey = 'AIzaSyDAmhStJ2lEeZG4qiqEpb92YrShfaDY6DE'; // Váš API klíč
$spreadsheetId = '1ZbaVVX2tJj7kWYczWopJMNZ7oU8YtXcGwp2EKgZ7XQo'; // Vaše ID tabulky
$range = 'A2:G8';
$current_domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$saturday_data = null;
$error_message = '';

function e_safe($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

function get_czech_day_name_q($date_string) {
    if (empty($date_string)) return '';
    $days_map = ['Monday'=>'Pondělí', 'Tuesday'=>'Úterý', 'Wednesday'=>'Středa', 'Thursday'=>'Čtvrtek', 'Friday'=>'Pátek', 'Saturday'=>'Sobota', 'Sunday'=>'Neděle'];
    try { $date_obj = new DateTime($date_string); return $days_map[$date_obj->format('l')] ?? ''; } catch (Exception $e) { return ''; }
}


if (function_exists('curl_init')) {
    $apiUrl = sprintf('https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s?key=%s', $spreadsheetId, $range, $apiKey);
    $ch = curl_init();
    curl_setopt_array($ch, [CURLOPT_URL => $apiUrl, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Referer: ' . $current_domain]]);
    $json_data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200 && $json_data) {
        $data = json_decode($json_data, true);
        $values = $data['values'] ?? [];
        foreach ($values as $row) {
            $date = $row[0] ?? '';
            if (get_czech_day_name_q($date) === 'Sobota') {
                $saturday_data = $row;
                break;
            }
        }
        if (!$saturday_data) {
            $error_message = 'Data pro sobotu nebyla nalezena.';
        }
    } else {
        $error_details = json_decode($json_data, true);
        $google_error = isset($error_details['error']['message']) ? e_safe($error_details['error']['message']) : 'Žádné další detaily.';
        $error_message = "Chyba při načítání dat z Google API (HTTP kód: ".e_safe($http_code)."). Detail: ".$google_error;
    }
} else {
    $error_message = "Na serveru chybí cURL rozšíření pro PHP.";
}
?>

<link rel="stylesheet" id="otazky-css" href="<?php echo get_stylesheet_directory_uri(); ?>/css/otazky.css" type="text/css" media="all" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">


<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="question-form-container">

            <h1><?php echo isset($saturday_data[2]) ? e_safe($saturday_data[2]) : 'Otázky k zamyšlení'; ?></h1>
            <p class="form-description">Odpovězte na otázky a zařaďte se do týdenního slosování o cenu.</p>

            <?php if ($form_sent): ?>
                <div class="form-success-message">
                    Děkujeme za odeslání odpovědí! Byli jste zařazeni do slosování.
                </div>
            <?php elseif (!empty($form_error)): ?>
                <div class="form-error-message">
                    <?php echo $form_error; ?>
                </div>
            <?php endif; ?>


            <?php if ($saturday_data && !$form_sent): ?>
                <?php
                    $questions_text = $saturday_data[3] ?? '';
                    $questions = preg_split('/\r\n|\r|\n/', $questions_text);
                    $questions = array_filter(array_map('trim', $questions));
                ?>
                <?php if (!empty($questions)): ?>
                    <form action="" method="post" class="questions-form">
                        <?php wp_nonce_field('submit_questions_action', 'question_form_nonce'); ?>

                        <div class="form-group">
                            <label for="jmeno_prijmeni">Jméno a příjmení (nepovinné)</label>
                            <input type="text" id="jmeno_prijmeni" name="jmeno_prijmeni">
                        </div>

                        <div class="form-group">
                            <label for="email_address">E-mail <span class="required">*</span></label>
                            <input type="email" id="email_address" name="email_address" required>
                        </div>

                        <?php foreach ($questions as $index => $question): ?>
                            <div class="form-group">
                                <label for="odpoved_<?php echo $index; ?>"><?php echo e_safe($question); ?></label>
                                <input type="hidden" name="otazky[<?php echo $index; ?>]" value="<?php echo e_safe($question); ?>">
                                <textarea id="odpoved_<?php echo $index; ?>" name="odpovedi[<?php echo $index; ?>]" rows="4"></textarea>
                            </div>
                        <?php endforeach; ?>

                        <div class="form-submit">
                            <button type="submit" name="submit_questions">Odeslat a zařadit do slosování</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p class="error-message">V tabulce nebyly pro sobotu nalezeny žádné otázky.</p>
                <?php endif; ?>

            <?php elseif ($error_message): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

        </div>
    </main>
</div>

<?php
get_footer();
?>