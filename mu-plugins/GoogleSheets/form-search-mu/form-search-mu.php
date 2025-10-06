<?php
/**
 * Plugin Name: Press Search (MU)
 * Description: MU-плагин: [press_search] + включение/выключение из админки и CSV URL. Парсинг CSV, поиск по Member ID/Press ID, вывод как в старом блоке.
 * Version: 1.1.0
 * Author: DeVolaris
 */

if (!defined('ABSPATH')) exit;

/* =========================
 *  Админ-настройки
 * ========================= */
add_action('admin_menu', function () {
    add_options_page(
        __('Press Search', 'press-search-mu'),
        __('Press Search', 'press-search-mu'),
        'manage_options',
        'press-search-mu',
        'press_search_mu_settings_page'
    );
});

add_action('admin_init', function () {
    register_setting('press_search_mu_group', 'press_search_mu_enabled', [
        'type' => 'boolean',
        'sanitize_callback' => fn($v) => (bool)$v,
        'default' => true,
    ]);
    register_setting('press_search_mu_group', 'press_search_mu_csv_url', [
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
        // Твой линк по умолчанию:
        'default' => 'https://docs.google.com/spreadsheets/d/e/2PACX-1vQDOG0yGPy-H9mXPSQ-tBdvN-pVC5WeRC6ME5Il9IJABbuXMOFNtzSab-C32XVvX_hDaEb7-ATB_KOj/pub?gid=1456626928&single=true&output=csv',
    ]);
});

function press_search_mu_settings_page(){ ?>
    <div class="wrap">
        <h1><?php esc_html_e('Press Search', 'press-search-mu'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('press_search_mu_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable search block', 'press-search-mu'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="press_search_mu_enabled" value="1"
                                   <?php checked((bool)get_option('press_search_mu_enabled', true)); ?>>
                            <?php esc_html_e('Show block where the [press_search] shortcode is placed', 'press-search-mu'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('CSV URL (Google Sheets → Publish to CSV)', 'press-search-mu'); ?></th>
                    <td>
                        <input type="url" class="regular-text" name="press_search_mu_csv_url"
                               value="<?php echo esc_attr(get_option('press_search_mu_csv_url','')); ?>"
                               placeholder="https://docs.google.com/.../pub?output=csv">
                        <p class="description">
                            <?php esc_html_e('Можно также передать в шорткоде: [press_search csv="..."]', 'press-search-mu'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
<?php }

/* =========================
 *  Шорткод
 * ========================= */
add_shortcode('press_search', function($atts = []){
    if (!(bool)get_option('press_search_mu_enabled', true)){
        if (current_user_can('manage_options')){
            return '
<div class="warn info">
    <h3><strong>Для перевірки дійсності посвідчень, </strong><strong>зробіть звернення до Редакції Журналу:</strong></h3>
    <h3>Телефоном: <a href="tel:+380954568703"><strong>+380 95 456 8703</strong></a></h3>
    <h3>Ел.адресою: <a href="mailto:info@policemartialarts.com.ua"><strong>info@policemartialarts.com.ua</strong></a></h3>
</div>
            ';
        }
        return '';
    }

    $atts = shortcode_atts([
        'csv' => get_option('press_search_mu_csv_url',''),
    ], $atts, 'press_search');

    ob_start(); ?>
    <div class="dv-press-search" data-csv="<?php echo esc_attr($atts['csv']); ?>">
        <style>
            /* Скоупим стили в контейнер, чтобы не трогать тему */
            .dv-press-search{font-family:Arial,sans-serif;display:flex;justify-content:center}
            .dv-press-search .container{background:#fff;padding:20px;border-radius:8px;box-shadow:0 0 10px rgba(0,0,0,.1);max-width:400px;width:100%;text-align:center}
            .dv-press-search input[type="text"]{width:calc(50% - 22px);padding:10px;border:1px solid #ccc;border-radius:4px;margin-bottom:10px}
            .dv-press-search button{padding:10px 20px;border:0;border-radius:4px;background:#007bff;color:#fff;cursor:pointer;font-size:16px}
            .dv-press-search button:hover{background:#0056b3}
            .dv-press-search #result{margin-top:20px}
            .dv-press-search #result img{max-width:100%;height:auto;border:1px solid #ddd;border-radius:4px;padding:5px;background:#fff}
            .dv-press-search .info-title{font-weight:700}
            .dv-press-search #press-id .info-title{color:#383a87}
            .dv-press-search #press-fullname{text-decoration:none;text-transform:uppercase;font-weight:800}
            .dv-press-search #press-status .TRUE{color:green}
            .dv-press-search .notrezult{color:red}
            .dv-press-search #press-status .FALSE{color:red;text-decoration:line-through}
            .dv-press-search #press-status #Canceled,
            .dv-press-search #press-status #Current,
            .dv-press-search #press-status #Canceled.TRUE,
            .dv-press-search #press-status #Current.FALSE{display:none}
            .dv-press-search #press-status #Canceled.FALSE,
            .dv-press-search #press-status #Current.TRUE{display:inline-block}
            .dv-press-search #press-status #Current.TRUE,
            .dv-press-search #press-status #Cancelled.FALSE{display:inline-block}
            .dv-press-search div#press-photo.FALSE:before{
                content:"";width:calc(100% + 30px);background:red;height:3px;position:absolute;transform:rotate3d(0,0,1,33deg);margin-top:116px;margin-left:-35px}
            .dv-press-search div#press-photo.FALSE:after{
                content:"";width:calc(100% + 30px);background:red;height:3px;position:absolute;transform:rotate3d(0,0,1,-33deg);margin-top:116px;margin-left:-395px}
        </style>

        <div class="container">
            <h3>Пошук посвідчення за №</h3>
            <input type="text" id="dv-memberID" placeholder="Введіть № ID" onkeypress="dvHandleKeyPress(event)">
            <button onclick="dvSearchMemberID()">Пошук</button>
            <div id="result"></div>
        </div>

        <script>
            const dvRoot = document.currentScript.closest('.dv-press-search');
            const dvCsvUrl = dvRoot ? dvRoot.dataset.csv : '';

            /* --- Устойчивый CSV-парсер (кавычки/запятые внутри ячеек) --- */
            function dvParseCSV(csv) {
                const rows = [];
                let row = [], cell = '', inQuotes = false;
                for (let i=0;i<csv.length;i++){
                    const ch = csv[i], next = csv[i+1];
                    if (ch === '"'){
                        if (inQuotes && next === '"'){ cell += '"'; i++; }
                        else { inQuotes = !inQuotes; }
                    } else if (ch === ',' && !inQuotes){
                        row.push(cell); cell = '';
                    } else if ((ch === '\n' || ch === '\r') && !inQuotes){
                        if (cell.length || row.length){ row.push(cell); rows.push(row); row = []; cell = ''; }
                    } else {
                        cell += ch;
                    }
                }
                if (cell.length || row.length){ row.push(cell); rows.push(row); }
                return rows;
            }

            function dvCsvToJSON(csv){
                const matrix = dvParseCSV(csv).filter(r => r.length && r.join('').trim() !== '');
                if (!matrix.length) return [];
                const headers = matrix[0].map(h => h.trim());
                return matrix.slice(1).map(r => {
                    const obj = {};
                    headers.forEach((h, idx) => obj[h] = (r[idx] || '').trim());
                    return obj;
                });
            }

            async function dvFetchData(){
                if (!dvCsvUrl) return [];
                try{
                    const response = await fetch(dvCsvUrl, {cache:'no-store'});
                    const data = await response.text();
                    return dvCsvToJSON(data);
                }catch(e){
                    console.error('PressSearch CSV fetch error:', e);
                    return [];
                }
            }

            /* --- Маппинг полей (UA/RU/EN варианты названий колонок) --- */
            const FIELDS = {
                MEMBER_ID:   ['Member ID','ID','Номер','ID КОРЕСПОНДЕНТА'],
                PRESS_ID:    ['Press ID','ID PRESS','ID ПРЕСА','№ посвідчення'],
                SURNAME:     ['Surname','SName','Прізвище','Фамилия'],
                NAME:        ['Name','Імʼя','Имя'],
                MNAME:       ['MiddleName','MName','По батькові','Отчество'],
                POSITION:    ['Position','Посада'],
                DATE_ISSUED: ['Date issued','Видане','ВИДАНЕ','ВИДАНО'],
                DATE_VALID:  ['Date valid','Дійсне до','ДІЙСНЕ ДО'],
                STATUS:      ['Status canceled','Status','Статус','Стан'], // TRUE/FALSE/текст
                PHOTO:       ['URL photo','Photo','Фото','Image'],
            };

            function dvPick(row, keys){
                for (const k of keys){ if (row[k] && row[k].trim() !== '') return row[k].trim(); }
                return '';
            }

            function dvStatusClass(val){
                const v = (val || '').toString().toLowerCase();
                if (['false','анульовано','cancelled','canceled','відмінено'].includes(v)) return 'FALSE';
                return 'TRUE';
            }

            function dvFIO(row){
                const s = dvPick(row, FIELDS.SURNAME);
                const n = dvPick(row, FIELDS.NAME);
                const m = dvPick(row, FIELDS.MNAME);
                return [s,n,m].filter(Boolean).join(' ').toUpperCase();
            }

            async function dvSearchMemberID(){
                const q = dvRoot.querySelector('#dv-memberID').value.trim();
                const resultDiv = dvRoot.querySelector('#result');
                const data = await dvFetchData();

                const match = data.find(row =>
                    dvPick(row, FIELDS.MEMBER_ID) === q || dvPick(row, FIELDS.PRESS_ID) === q
                );

                if (!match){
                    resultDiv.innerHTML = '<span class="notrezult">За данним запросом дані відсутні !</span>';
                    return;
                }

                const pressId   = dvPick(match, FIELDS.PRESS_ID);
                const fio       = dvFIO(match);
                const position  = dvPick(match, FIELDS.POSITION);
                const issued    = dvPick(match, FIELDS.DATE_ISSUED);
                const validTill = dvPick(match, FIELDS.DATE_VALID);
                const statusCls = dvStatusClass(dvPick(match, FIELDS.STATUS));
                const photo     = dvPick(match, FIELDS.PHOTO);

                resultDiv.innerHTML =
                  `<div id="press-id" class="${pressId}">
                      <h3><span class="info-title">ID PRESS | ПОСВІДЧЕННЯ № </span><span>${pressId}</span></h3>
                   </div>
                   <div id="press-fullname"><h4>${fio}</h4></div>
                   <div id="press-status" class="${statusCls}">
                      <span class="info-title">ПОСАДА: </span>
                      <span class="position ${statusCls}">${position}</span>
                   </div>
                   <div id="press-date-valid">
                      <span class="info-title">ВИДАНЕ: </span><span>${issued}</span><br>
                      <span class="info-title">ДІЙСНЕ ДО: </span><span>${validTill}</span>
                   </div>
                   <div id="press-status" class="${statusCls}">
                      <span class="info-title">СТАТУС ПОСВІДЧЕННЯ: </span>
                      <span id="Canceled" class="${statusCls}">Анульовано</span>
                      <span id="Current" class="${statusCls}">Актуально</span>
                   </div>
                   <div id="press-photo" class="${statusCls}">
                      <img src="${photo}" alt="URL photo">
                   </div>`;
            }

            function dvHandleKeyPress(e){ if (e.key === 'Enter') dvSearchMemberID(); }
            window.dvSearchMemberID = dvSearchMemberID;
            window.dvHandleKeyPress = dvHandleKeyPress;
        </script>
    </div>
    <?php
    return ob_get_clean();
});
