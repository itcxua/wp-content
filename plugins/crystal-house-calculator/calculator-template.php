<div id="crystal-calculator" class="cc4-wrap">
    <style>
        .cc4-wrap{max-width:1000px;margin:24px auto;font:14px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,"Helvetica Neue",Arial,sans-serif;color:#111}
        .cc4-card{background:#fff;border:1px solid #e5e7eb;border-radius:16px;padding:20px;box-shadow:0 6px 22px rgba(0,0,0,.06)}
        .cc4-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:16px}
        .cc4-col-12{grid-column:span 12}.cc4-col-8{grid-column:span 8}.cc4-col-6{grid-column:span 6}.cc4-col-4{grid-column:span 4}.cc4-col-3{grid-column:span 3}
        @media (max-width:900px){.cc4-col-8,.cc4-col-6,.cc4-col-4,.cc4-col-3{grid-column:span 12}}
        .cc4-field{display:flex;flex-direction:column;gap:6px}
        .cc4-field label{font-weight:600}
        .cc4-field input,.cc4-field select{border:1px solid #d1d5db;border-radius:10px;padding:10px 12px}
        .cc4-opt{display:flex;align-items:center;gap:10px;margin:6px 0;flex-wrap:wrap}
        .cc4-small{font-size:12px;color:#6b7280}
        .cc4-total{background:#0a7d55;color:#fff;border-radius:12px;padding:16px 18px;display:flex;justify-content:space-between;align-items:center}
        .cc4-break{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:14px}
        .cc4-btn{display:inline-flex;align-items:center;gap:8px;border:0;border-radius:12px;padding:12px 16px;background:#111;color:#fff;cursor:pointer;transition:background-color 0.2s}
        .cc4-btn:hover{background:#333}
        .cc4-btn:disabled{background:#999;cursor:not-allowed}
        .cc4-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        details{border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;background:#fcfdff}
        summary{cursor:pointer;font-weight:700}
        .cc4-success{background:#10b981;color:#fff;padding:12px;border-radius:8px;margin:10px 0}
        .cc4-error{background:#ef4444;color:#fff;padding:12px;border-radius:8px;margin:10px 0}
        .phone-error{color:#ef4444;font-size:12px;margin-top:4px}
    </style>
    
    <div class="cc4-card">
        <div class="cc4-grid">
            <div class="cc4-col-12">
                <div style="font-size:20px;font-weight:800">Калькулятор приблизного розрахунку — Crystal House</div>
                <div class="cc4-small">Мінімальне замовлення: ₴2000. Ціна може змінюватися залежно від ступеня забрудненості. 5-те прибирання: −15%.</div>
            </div>
            
            <!-- Контактна інформація -->
            <div class="cc4-col-12">
                <div style="font-size:18px;font-weight:700;margin:10px 0">Контактна інформація</div>
            </div>
            <div class="cc4-col-6">
                <div class="cc4-field">
                    <label for="clientName">Ім'я клієнта <span style="color:red">*</span></label>
                    <input id="clientName" type="text" placeholder="Введіть ваше ім'я" required maxlength="50">
                </div>
            </div>
            <div class="cc4-col-6">
                <div class="cc4-field">
                    <label for="clientPhone">Номер телефону <span style="color:red">*</span></label>
                    <input id="clientPhone" type="tel" placeholder="+380 XX XXX XX XX" required maxlength="17">
                    <div id="phoneError" class="phone-error" style="display:none"></div>
                </div>
            </div>
            
            <!-- Пакет + площа -->
            <div class="cc4-col-6">
                <div class="cc4-field">
                    <label for="pkg">Пакет</label>
                    <select id="pkg">
                        <option value="support">Підтримуючий (таблиця)</option>
                        <option value="basic">Базовий (ставка грн/м²)</option>
                        <option value="general">Генеральне (80–100 грн/м²)</option>
                        <option value="postreno">Після ремонту (60–90 грн/м²)</option>
                    </select>
                </div>
            </div>
            <div class="cc4-col-3">
                <div class="cc4-field">
                    <label for="area">Площа, м²</label>
                    <input id="area" type="number" min="10" step="1" value="60">
                </div>
            </div>
            <div class="cc4-col-3">
                <div class="cc4-field">
                    <label for="soil">Забрудненість (до бази)</label>
                    <select id="soil">
                        <option value="0.90">Низька (−10%)</option>
                        <option value="1.00" selected>Стандарт</option>
                        <option value="1.15">Середня (+15%)</option>
                        <option value="1.30">Висока (+30%)</option>
                        <option value="1.50">Дуже висока (+50%)</option>
                    </select>
                </div>
            </div>
            
            <!-- Ставка грн/м² -->
            <div class="cc4-col-6" id="rateRow">
                <div class="cc4-field">
                    <label>Ставка, грн/м²</label>
                    <select id="rate" style="display:none"></select>
                    <input id="rateInput" type="number" min="1" step="1" value="50" style="width:140px;display:none">
                    <div class="cc4-small" id="rateHint"></div>
                </div>
            </div>
            
            <!-- Валюта -->
            <div class="cc4-col-6">
                <div class="cc4-field">
                    <label>Валюта</label>
                    <div class="cc4-row">
                        <select id="currency">
                            <option value="UAH">UAH ₴</option>
                            <option value="EUR">EUR €</option>
                        </select>
                        <span class="cc4-small">Курс EUR→UAH:</span>
                        <input id="eurRate" type="number" step="0.01" value="45" style="width:110px">
                    </div>
                </div>
            </div>
            
            <!-- Санвузли/унітази -->
            <div class="cc4-col-3">
                <div class="cc4-field">
                    <label for="baths">Санвузли, шт</label>
                    <input id="baths" type="number" min="1" step="1" value="1">
                    <div class="cc4-small">1-й входить; з 2-го — ₴600/шт</div>
                </div>
            </div>
            <div class="cc4-col-3">
                <div class="cc4-field">
                    <label for="toilets">Унітази, шт</label>
                    <input id="toilets" type="number" min="1" step="1" value="1">
                    <div class="cc4-small">з 2-го — ₴200/шт</div>
                </div>
            </div>
            
            <!-- Виїзд за місто -->
            <div class="cc4-col-6">
                <div class="cc4-field">
                    <label for="distance">Виїзд за місто, км</label>
                    <input id="distance" type="number" min="0" step="1" value="0">
                    <div class="cc4-small">₴30/км (Підтримуючий) · ₴40/км (Базовий) · ₴50/км (Генеральне/Після ремонту)</div>
                </div>
            </div>
            
            <!-- Накопичувальна знижка -->
            <div class="cc4-col-12">
                <div class="cc4-opt">
                    <input type="checkbox" id="chk_loyal">
                    <label for="chk_loyal">Це 5-те прибирання (накопичувальна знижка −15%)</label>
                    <span class="cc4-small">Застосовується перед мінімалкою ₴2000</span>
                </div>
            </div>
            
            <!-- Вікна -->
            <div class="cc4-col-12">
                <div class="cc4-field">
                    <label>Миття вікон</label>
                    <div class="cc4-opt">
                        <input type="checkbox" id="opt_windows">
                        <label for="opt_windows">Включити</label>
                        <span class="cc4-small">₴200–240 / м² скла</span>
                        <span class="cc4-small">Площа скла (м²):</span>
                        <input id="sqm_windows" type="number" min="1" value="10" style="width:120px;display:none">
                        <span class="cc4-small">Ставка:</span>
                        <select id="rate_windows" style="display:none">
                            <option value="200">200</option>
                            <option value="240" selected>240</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Балкон/Лоджія -->
            <div class="cc4-col-12">
                <div class="cc4-field">
                    <label>Зовнішні зони</label>
                    <div class="cc4-opt">
                        <input type="checkbox" id="opt_balcony">
                        <label for="opt_balcony">Балкон (звичайний) — ₴250/шт</label>
                        <input id="qty_balcony" type="number" min="1" value="1" style="width:90px;display:none">
                    </div>
                    <div class="cc4-opt">
                        <input type="checkbox" id="opt_loggia">
                        <label for="opt_loggia">Лоджія — ₴500/шт</label>
                        <input id="qty_loggia" type="number" min="1" value="1" style="width:90px;display:none">
                    </div>
                </div>
            </div>
            
            <!-- Додаткові послуги -->
            <div class="cc4-col-12">
                <details>
                    <summary>Додаткові послуги</summary>
                    <div class="cc4-field" style="margin-top:10px">
                        <div class="cc4-opt"><input type="checkbox" id="opt_walls"><label for="opt_walls">Миття стін — ₴500/год</label><input id="qty_walls" type="number" min="1" value="1" style="width:90px;display:none"><span class="cc4-small" id="lbl_walls" style="display:none">год</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_ironing"><label for="opt_ironing">Прасування — ₴350/год</label><input id="qty_ironing" type="number" min="1" value="2" style="width:90px;display:none"><span class="cc4-small" id="lbl_ironing" style="display:none">год</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_dishes"><label for="opt_dishes">Миття посуду — ₴350/год</label><input id="qty_dishes" type="number" min="1" value="2" style="width:90px;display:none"><span class="cc4-small" id="lbl_dishes" style="display:none">год</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_pettray"><label for="opt_pettray">Миття лотка домашнього улюбленця — ₴200</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_laundry"><label for="opt_laundry">Прання — ₴200/цикл</label><input id="qty_laundry" type="number" min="1" value="1" style="width:90px;display:none"><span class="cc4-small" id="lbl_laundry" style="display:none">цикл</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_hood"><label for="opt_hood">Миття витяжки — ₴400</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_oven"><label for="opt_oven">Миття духовки — ₴500</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_mwave"><label for="opt_mwave">Мікрохвильова — ₴300</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_fridge"><label for="opt_fridge">Холодильник — ₴500</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_kitchencab"><label for="opt_kitchencab">Шафи кухонні — ₴400/год</label><input id="qty_kitchencab" type="number" min="1" value="2" style="width:90px;display:none"><span class="cc4-small" id="lbl_kitchencab" style="display:none">год</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_grill"><label for="opt_grill">Миття грилю — ₴300</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_addbath"><label for="opt_addbath">Додатковий санвузол — ₴600/шт</label><input id="qty_addbath" type="number" min="1" value="1" style="width:90px;display:none"></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_addtoilet"><label for="opt_addtoilet">Додатковий унітаз — ₴200/шт</label><input id="qty_addtoilet" type="number" min="1" value="1" style="width:90px;display:none"></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_organize"><label for="opt_organize">Складання речей — ₴300/год</label><input id="qty_organize" type="number" min="1" value="2" style="width:90px;display:none"><span class="cc4-small" id="lbl_organize" style="display:none">год</span></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_curtains"><label for="opt_curtains">Штори/тюлі — введіть суму</label><input id="amt_curtains" type="number" min="400" value="400" step="50" style="width:140px;display:none"></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_vacuum"><label for="opt_vacuum">Пилосос компанії — ₴400</label></div>
                        <div class="cc4-opt"><input type="checkbox" id="opt_keys"><label for="opt_keys">Доставка ключів (місто, 1 адреса) — ₴350</label></div>
                    </div>
                </details>
            </div>
            
            <!-- Хімчистка -->
            <div class="cc4-col-12">
                <details>
                    <summary>Хімчистка мʼяких меблів та килимів (додати)</summary>
                    <div class="cc4-field" style="margin-top:10px">
                        <div class="cc4-opt"><label>Диван 2 місця — ₴1500</label><input id="chem_sofa2" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Диван 3 місця — ₴2000</label><input id="chem_sofa3" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Диван 4 місця — ₴2500</label><input id="chem_sofa4" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Диван 5 місць — ₴3000</label><input id="chem_sofa5" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Диван 6 місць — ₴3500</label><input id="chem_sofa6" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Крісло — ₴500</label><input id="chem_armchair" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Матрац двомісний — ₴1500</label><input id="chem_mattress2" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Матрац одномісний — ₴800</label><input id="chem_mattress1" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Стільці — ₴250/шт</label><input id="chem_chair" type="number" min="0" value="0" style="width:90px"></div>
                        <div class="cc4-opt"><label>Бильце ліжка — ₴ (500–1000)</label><input id="chem_headboard" type="number" min="0" step="50" value="0" style="width:120px"></div>
                        <div class="cc4-opt"><label>Подушка — ₴60–100/шт</label><input id="chem_pillow_qty" type="number" min="0" value="0" style="width:90px"><span class="cc4-small">×</span><input id="chem_pillow_rate" type="number" min="60" max="100" value="60" step="10" style="width:90px"><span class="cc4-small">грн/шт</span></div>
                    </div>
                </details>
            </div>
            
            <!-- Total -->
            <div class="cc4-col-12">
                <div class="cc4-total">
                    <div>
                        <div class="cc4-small">Орієнтовна вартість</div>
                        <div id="totalLabel" style="font-size:28px;font-weight:800">—</div>
                    </div>
                    <button id="cta" class="cc4-btn">Залишити заявку</button>
                </div>
            </div>
            
            <!-- Message area -->
            <div class="cc4-col-12" id="messageArea" style="display:none"></div>
            <div class="cc4-col-12">
                <div class="cc4-break">
                    <div class="cc4-small" style="color:#475569">Деталізація</div>
                    <div id="breakdown"></div>
                </div>
            </div>
        </div>
    </div>
</div>
 