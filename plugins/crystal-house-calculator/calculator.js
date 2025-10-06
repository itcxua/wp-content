jQuery(document).ready(function($) {
    console.log('Crystal Calculator loaded'); // Для отладки
    
    const cfg = {
        supportTable: [
            {max:50, price:1500},
            {max:80, price:1800},
            {max:110, price:2000},
            {max:130, price:2200},
            {max:150, price:2500},
            {max:200, price:2800},
            {max:Infinity, price:3000}
        ],
        generalRates: [80,90,100],
        postrenoRates: [60,75,90],
        globalMinOrder: 2000,
        bathroomFromSecond: 600,
        toiletFromSecond: 200,
        windowsRates: [200,240],
        balcony: 250,
        loggia: 500,
        travelPerKm: { support:30, basic:40, general:50, postreno:50 }
    };

    // DOM elements
    const el = id => document.getElementById(id);
    const pkg = el('pkg'), area = el('area'), soil = el('soil');
    const rateSel = el('rate'), rateInput = el('rateInput'), rateRow = el('rateRow'), rateHint = el('rateHint');
    const currency = el('currency'), eurRate = el('eurRate');
    const baths = el('baths'), toilets = el('toilets'), distance = el('distance');
    const chkLoyal = el('chk_loyal');
    
    // Contact fields
    const clientName = el('clientName'), clientPhone = el('clientPhone'), phoneError = el('phoneError');
    
    // Windows
    const chkWin = el('opt_windows'), sqmWin = el('sqm_windows'), rateWin = el('rate_windows');
    
    // External zones
    const chkBal = el('opt_balcony'), qtyBal = el('qty_balcony');
    const chkLog = el('opt_loggia'), qtyLog = el('qty_loggia');
    
    // Additional services elements
    const chkWalls = el('opt_walls'), qtyWalls = el('qty_walls'), lblWalls = el('lbl_walls');
    const chkIron = el('opt_ironing'), qtyIron = el('qty_ironing'), lblIron = el('lbl_ironing');
    const chkDishes = el('opt_dishes'), qtyDishes = el('qty_dishes'), lblDishes = el('lbl_dishes');
    const chkPet = el('opt_pettray');
    const chkLaundry = el('opt_laundry'), qtyLaundry = el('qty_laundry'), lblLaundry = el('lbl_laundry');
    const chkHood = el('opt_hood'), chkOven = el('opt_oven'), chkMW = el('opt_mwave'), chkFridge = el('opt_fridge');
    const chkKC = el('opt_kitchencab'), qtyKC = el('qty_kitchencab'), lblKC = el('lbl_kitchencab');
    const chkGrill = el('opt_grill'), chkAB = el('opt_addbath'), qtyAB = el('qty_addbath');
    const chkAT = el('opt_addtoilet'), qtyAT = el('qty_addtoilet');
    const chkOrg = el('opt_organize'), qtyOrg = el('qty_organize'), lblOrg = el('lbl_organize');
    const chkCurt = el('opt_curtains'), amtCurt = el('amt_curtains');
    const chkVac = el('opt_vacuum'), chkKeys = el('opt_keys');
    
    const totalLabel = el('totalLabel'), breakdown = el('breakdown'), cta = el('cta');
    const messageArea = el('messageArea');

    // Phone validator functions
    function validatePhone(phone) {
        // Удаляем все символы кроме цифр
        const cleanPhone = phone.replace(/\D/g, '');
        
        // Проверяем что начинается с 380 и длина правильная
        if (cleanPhone.length === 12 && cleanPhone.startsWith('380')) {
            return { valid: true, message: '' };
        } else if (cleanPhone.length === 10 && cleanPhone.startsWith('0')) {
            return { valid: true, message: '' };
        } else if (cleanPhone.length < 10) {
            return { valid: false, message: 'Номер телефону занадто короткий' };
        } else if (cleanPhone.length > 12) {
            return { valid: false, message: 'Номер телефону занадто довгий' };
        } else if (!cleanPhone.startsWith('380') && !cleanPhone.startsWith('0')) {
            return { valid: false, message: 'Номер повинен починатися з +380 або 0' };
        }
        
        return { valid: false, message: 'Невірний формат номера телефону' };
    }

    function formatPhone(value) {
        // Удаляем все нецифровые символы
        let numbers = value.replace(/\D/g, '');
        
        // Если начинается с 8, заменяем на 380
        if (numbers.startsWith('8')) {
            numbers = '380' + numbers.substring(1);
        }
        
        // Если начинается с 0, добавляем 38 в начало
        if (numbers.startsWith('0')) {
            numbers = '38' + numbers;
        }
        
        // Если не начинается с 380, добавляем 380
        if (!numbers.startsWith('380') && numbers.length > 0) {
            numbers = '380' + numbers;
        }
        
        // Ограничиваем длину
        numbers = numbers.substring(0, 12);
        
        // Форматируем как +380 XX XXX XX XX
        if (numbers.length >= 3) {
            let formatted = '+' + numbers.substring(0, 3);
            if (numbers.length > 3) {
                formatted += ' ' + numbers.substring(3, 5);
            }
            if (numbers.length > 5) {
                formatted += ' ' + numbers.substring(5, 8);
            }
            if (numbers.length > 8) {
                formatted += ' ' + numbers.substring(8, 10);
            }
            if (numbers.length > 10) {
                formatted += ' ' + numbers.substring(10, 12);
            }
            return formatted;
        }
        
        return numbers ? '+' + numbers : '';
    }

    // Phone input event listeners
    if (clientPhone) {
        clientPhone.addEventListener('input', function(e) {
            const formatted = formatPhone(e.target.value);
            e.target.value = formatted;
            
            const validation = validatePhone(formatted);
            if (!validation.valid && formatted.length > 0) {
                phoneError.textContent = validation.message;
                phoneError.style.display = 'block';
                clientPhone.style.borderColor = '#ef4444';
            } else {
                phoneError.style.display = 'none';
                clientPhone.style.borderColor = '#d1d5db';
            }
        });
    }

    // Name validation
    if (clientName) {
        clientName.addEventListener('input', function(e) {
            // Разрешаем только буквы, пробелы, дефисы и апострофы
            e.target.value = e.target.value.replace(/[^a-zA-Zа-яА-ЯёЁіІїЇєЄ\s\-\']/g, '');
        });
    }

    // Helper functions
    function safeInt(v, d = 0) { 
        const n = parseInt(v, 10); 
        return Number.isFinite(n) ? n : d; 
    }
    
    function safeNum(v, d = 0) { 
        const n = parseFloat(v); 
        return Number.isFinite(n) ? n : d; 
    }
    
    function fmt(amountUAH) {
        const useEUR = currency && currency.value === 'EUR';
        const rate = Math.max(0.01, safeNum(eurRate ? eurRate.value : 45, 45));
        const val = useEUR ? (amountUAH / rate) : amountUAH;
        const symbol = useEUR ? '€' : '₴';
        return symbol + ' ' + (useEUR ? val.toFixed(2) : Math.round(val).toLocaleString('uk-UA'));
    }
    
    function toggle(elm, show) { 
        if (elm) elm.style.display = show ? '' : 'none'; 
    }
    
    function toggleQty(chk, qty, lbl) { 
        if (qty) toggle(qty, chk.checked); 
        if (lbl) toggle(lbl, chk.checked); 
    }

    function showMessage(text, isError = false) {
        if (messageArea) {
            messageArea.innerHTML = `<div class="${isError ? 'cc4-error' : 'cc4-success'}">${text}</div>`;
            toggle(messageArea, true);
            setTimeout(() => toggle(messageArea, false), 5000);
        }
    }

    function refreshRateSelector() {
        if (!pkg) return;
        
        if (pkg.value === 'support') {
            toggle(rateRow, false);
            return;
        }
        toggle(rateRow, true);
        if (pkg.value === 'basic') {
            toggle(rateSel, false);
            toggle(rateInput, true);
            if (rateHint) rateHint.textContent = 'Базовий: задайте власну ставку грн/м² (за замовчуванням 50).';
            return;
        }
        toggle(rateInput, false);
        toggle(rateSel, true);
        if (rateSel) {
            rateSel.innerHTML = '';
            let rates = pkg.value === 'general' ? [80, 90, 100] : [60, 75, 90];
            rates.forEach(r => {
                const o = document.createElement('option');
                o.value = String(r);
                o.textContent = r;
                rateSel.appendChild(o);
            });
        }
        if (rateHint) rateHint.textContent = pkg.value === 'general' ? 'Генеральне: 80/90/100 грн/м²' : 'Після ремонту: 60/75/90 грн/м²';
    }

    function priceSupport(area) {
        for (const row of cfg.supportTable) {
            if (area <= row.max) return row.price;
        }
        return cfg.supportTable[cfg.supportTable.length - 1].price;
    }

    function collectFormData() {
        const pkgVal = pkg ? pkg.value : 'support';
        const A = Math.max(10, safeNum(area ? area.value : 60, 60));
        const soilK = safeNum(soil ? soil.value : 1.0, 1.0);
        
        // Package names mapping
        const packageNames = {
            'support': 'Підтримуючий',
            'basic': 'Базовий',
            'general': 'Генеральне',
            'postreno': 'Після ремонту'
        };
        const soilNames = {
            '0.90': 'Низька (-10%)',
            '1.00': 'Стандарт',
            '1.15': 'Середня (+15%)',
            '1.30': 'Висока (+30%)',
            '1.50': 'Дуже висока (+50%)'
        };

        const data = {
            // Contact info
            clientName: clientName ? clientName.value.trim() : '',
            clientPhone: clientPhone ? clientPhone.value.trim() : '',
            
            // Basic info
            package: packageNames[pkgVal] || pkgVal,
            area: A,
            soil: soilNames[soil ? soil.value : '1.00'] || 'Стандарт',
            bathrooms: Math.max(1, safeInt(baths ? baths.value : 1, 1)),
            toilets: Math.max(1, safeInt(toilets ? toilets.value : 1, 1)),
            distance: Math.max(0, safeNum(distance ? distance.value : 0, 0)),
            currency: currency ? currency.value : 'UAH',
            loyaltyDiscount: chkLoyal ? chkLoyal.checked : false
        };

        if (currency && currency.value === 'EUR') {
            data.eurRate = safeNum(eurRate ? eurRate.value : 45, 45);
        }

        if (pkgVal !== 'support') {
            if (pkgVal === 'basic') {
                data.rate = Math.max(1, safeNum(rateInput ? rateInput.value : 50, 50));
            } else {
                data.rate = safeNum((rateSel ? rateSel.value : null) || (pkgVal === 'general' ? 90 : 75), 0);
            }
        }

        // Windows
        if (chkWin && chkWin.checked) {
            data.windows = true;
            data.windowsArea = Math.max(1, safeNum(sqmWin ? sqmWin.value : 1, 1));
            data.windowsRate = safeNum(rateWin ? rateWin.value : 240, 240);
        }

        // Balcony/Loggia
        if (chkBal && chkBal.checked) {
            data.balcony = true;
            data.balconyQty = Math.max(1, safeInt(qtyBal ? qtyBal.value : 1, 1));
        }
        if (chkLog && chkLog.checked) {
            data.loggia = true;
            data.loggiaQty = Math.max(1, safeInt(qtyLog ? qtyLog.value : 1, 1));
        }

        // Additional services
        const additionalServices = [
            { check: chkWalls, key: 'walls', qty: qtyWalls },
            { check: chkIron, key: 'ironing', qty: qtyIron },
            { check: chkDishes, key: 'dishes', qty: qtyDishes },
            { check: chkLaundry, key: 'laundry', qty: qtyLaundry },
            { check: chkKC, key: 'kitchencab', qty: qtyKC },
            { check: chkAB, key: 'addbath', qty: qtyAB },
            { check: chkAT, key: 'addtoilet', qty: qtyAT },
            { check: chkOrg, key: 'organize', qty: qtyOrg }
        ];

        additionalServices.forEach(service => {
            if (service.check && service.check.checked) {
                data[service.key] = true;
                if (service.qty) {
                    data[service.key + 'Qty'] = Math.max(1, safeInt(service.qty.value, 1));
                }
            }
        });

        // Fixed price services
        if (chkPet && chkPet.checked) data.pettray = true;
        if (chkHood && chkHood.checked) data.hood = true;
        if (chkOven && chkOven.checked) data.oven = true;
        if (chkMW && chkMW.checked) data.microwave = true;
        if (chkFridge && chkFridge.checked) data.fridge = true;
        if (chkGrill && chkGrill.checked) data.grill = true;
        if (chkVac && chkVac.checked) data.vacuum = true;
        if (chkKeys && chkKeys.checked) data.keys = true;

        if (chkCurt && chkCurt.checked) {
            data.curtains = true;
            data.curtainsAmount = Math.max(0, safeNum(amtCurt ? amtCurt.value : 400, 400));
        }

        // Chemical cleaning
        const chemServices = [
            'chem_sofa2', 'chem_sofa3', 'chem_sofa4', 'chem_sofa5', 'chem_sofa6',
            'chem_armchair', 'chem_mattress2', 'chem_mattress1', 'chem_chair', 'chem_headboard'
        ];
        chemServices.forEach(id => {
            const element = el(id);
            const qty = safeInt(element ? element.value || '0' : '0', 0);
            if (qty > 0) {
                const key = id.replace('chem_', 'chem') + (id === 'chem_headboard' ? '' : 'Qty');
                data[key] = qty;
            }
        });

        // Pillows
        const pillowQty = safeInt(el('chem_pillow_qty') ? el('chem_pillow_qty').value || '0' : '0', 0);
        const pillowRate = safeNum(el('chem_pillow_rate') ? el('chem_pillow_rate').value || '0' : '0', 0);
        if (pillowQty > 0 && pillowRate > 0) {
            data.chemPillow = pillowQty;
            data.chemPillowRate = pillowRate;
        }

        return data;
    }

    function calc() {
        if (!pkg || !area || !totalLabel) return;
        
        const pkgVal = pkg.value;
        const A = Math.max(10, safeNum(area.value, 60));
        const soilK = safeNum(soil ? soil.value : 1.0, 1.0);

        // Base calculation
        let base = 0;
        let baseDesc = '';
        if (pkgVal === 'support') {
            base = priceSupport(A);
            baseDesc = 'Підтримуючий (таблиця)';
        } else if (pkgVal === 'basic') {
            const r = Math.max(1, safeNum(rateInput ? rateInput.value : 50, 50));
            base = A * r * soilK;
            baseDesc = `Базовий (${A} м² × ₴${r}/м² × коеф. ${soilK})`;
        } else {
            const r = safeNum((rateSel ? rateSel.value : null) || (pkgVal === 'general' ? 90 : 75), 0);
            base = A * r * soilK;
            baseDesc = `${pkgVal === 'general' ? 'Генеральне' : 'Після ремонту'} (${A} м² × ₴${r}/м² × коеф. ${soilK})`;
        }

        // Bathrooms/toilets
        const B = Math.max(1, safeInt(baths ? baths.value : 1, 1));
        const T = Math.max(1, safeInt(toilets ? toilets.value : 1, 1));
        const bathsFee = Math.max(0, B - 1) * cfg.bathroomFromSecond;
        const toiletsFee = Math.max(0, T - 1) * cfg.toiletFromSecond;

        // Windows
        let windowsTotal = 0;
        if (chkWin && chkWin.checked) {
            const sqm = Math.max(1, safeNum(sqmWin ? sqmWin.value : 1, 1));
            const r = safeNum(rateWin ? rateWin.value : 240, 240);
            windowsTotal = sqm * r;
        }

        // External zones
        let extZones = 0;
        if (chkBal && chkBal.checked) extZones += Math.max(1, safeInt(qtyBal ? qtyBal.value : 1, 1)) * cfg.balcony;
        if (chkLog && chkLog.checked) extZones += Math.max(1, safeInt(qtyLog ? qtyLog.value : 1, 1)) * cfg.loggia;

        // Additional services
        let extrasTotal = 0;
        const extraLines = [];
        function add(label, price) { 
            extrasTotal += price; 
            extraLines.push({ label, price }); 
        }
        function addQty(label, unit, qty) { 
            const price = unit * qty; 
            extrasTotal += price; 
            extraLines.push({ label: `${label} (${qty})`, price }); 
        }

        if (chkWalls && chkWalls.checked) addQty('Миття стін (год)', 500, Math.max(1, safeInt(qtyWalls ? qtyWalls.value : 1, 1)));
        if (chkIron && chkIron.checked) addQty('Прасування (год)', 350, Math.max(1, safeInt(qtyIron ? qtyIron.value : 2, 2)));
        if (chkDishes && chkDishes.checked) addQty('Миття посуду (год)', 350, Math.max(1, safeInt(qtyDishes ? qtyDishes.value : 2, 2)));
        if (chkPet && chkPet.checked) add('Миття лотка', 200);
        if (chkLaundry && chkLaundry.checked) addQty('Прання (цикл)', 200, Math.max(1, safeInt(qtyLaundry ? qtyLaundry.value : 1, 1)));
        if (chkHood && chkHood.checked) add('Миття витяжки', 400);
        if (chkOven && chkOven.checked) add('Миття духовки', 500);
        if (chkMW && chkMW.checked) add('Мікрохвильова', 300);
        if (chkFridge && chkFridge.checked) add('Холодильник', 500);
        if (chkKC && chkKC.checked) addQty('Шафи кухонні (год)', 400, Math.max(1, safeInt(qtyKC ? qtyKC.value : 2, 2)));
        if (chkGrill && chkGrill.checked) add('Миття грилю', 300);
        if (chkAB && chkAB.checked) addQty('Дод. санвузол (шт)', 600, Math.max(1, safeInt(qtyAB ? qtyAB.value : 1, 1)));
        if (chkAT && chkAT.checked) addQty('Дод. унітаз (шт)', 200, Math.max(1, safeInt(qtyAT ? qtyAT.value : 1, 1)));
        if (chkOrg && chkOrg.checked) addQty('Складання речей (год)', 300, Math.max(1, safeInt(qtyOrg ? qtyOrg.value : 2, 2)));
        if (chkCurt && chkCurt.checked) add('Штори/тюлі (сума)', Math.max(0, safeNum(amtCurt ? amtCurt.value : 400, 400)));
        if (chkVac && chkVac.checked) add('Пилосос компанії', 400);
        if (chkKeys && chkKeys.checked) add('Доставка ключів', 350);

        // Chemical cleaning
        const chem = [
            ['chem_sofa2', 1500, 'Диван 2 місця'],
            ['chem_sofa3', 2000, 'Диван 3 місця'],
            ['chem_sofa4', 2500, 'Диван 4 місця'],
            ['chem_sofa5', 3000, 'Диван 5 місць'],
            ['chem_sofa6', 3500, 'Диван 6 місць'],
            ['chem_armchair', 500, 'Крісло'],
            ['chem_mattress2', 1500, 'Матрац двомісний'],
            ['chem_mattress1', 800, 'Матрац одномісний'],
            ['chem_chair', 250, 'Стільці (шт)']
        ];

        chem.forEach(([id, unit, label]) => {
            const element = el(id);
            const q = safeInt(element ? element.value || '0' : '0', 0);
            if (q > 0) {
                const price = unit * q;
                extrasTotal += price;
                extraLines.push({ label: `${label} (${q})`, price });
            }
        });

        // Headboard + pillows
        const hbElement = el('chem_headboard');
        const hb = safeNum(hbElement ? hbElement.value || '0' : '0', 0);
        if (hb > 0) {
            extrasTotal += hb;
            extraLines.push({ label: 'Бильце ліжка', price: hb });
        }

        const pQtyElement = el('chem_pillow_qty');
        const pRateElement = el('chem_pillow_rate');
        const pQty = safeInt(pQtyElement ? pQtyElement.value || '0' : '0', 0);
        const pRate = safeNum(pRateElement ? pRateElement.value || '0' : '0', 0);
        if (pQty > 0 && pRate > 0) {
            const price = pQty * pRate;
            extrasTotal += price;
            extraLines.push({ label: `Подушка (${pQty} × ₴${pRate})`, price });
        }

        // Travel fee
        const KM = Math.max(0, safeNum(distance ? distance.value : 0, 0));
        const travelRate = cfg.travelPerKm[pkgVal] || 0;
        const travelFee = KM * travelRate;

        // Subtotal
        let subtotal = base + bathsFee + toiletsFee + windowsTotal + extZones + extrasTotal + travelFee;

        // Loyalty discount
        let loyalCut = 0;
        if (chkLoyal && chkLoyal.checked) {
            loyalCut = subtotal * 0.15;
            subtotal -= loyalCut;
        }

        // Minimum order
        const appliedMin = subtotal < cfg.globalMinOrder;
        const totalUAH = appliedMin ? cfg.globalMinOrder : subtotal;

        // Render
        totalLabel.textContent = fmt(Math.max(0, totalUAH));

        if (breakdown) {
            const lines = [];
            lines.push(`<div>База: ${baseDesc}: <strong>${fmt(base)}</strong></div>`);
            if (B > 1) lines.push(`<div>Санвузли (з 2-го ${B - 1} × ₴${cfg.bathroomFromSecond}): <strong>${fmt(bathsFee)}</strong></div>`);
            if (T > 1) lines.push(`<div>Унітази (з 2-го ${T - 1} × ₴${cfg.toiletFromSecond}): <strong>${fmt(toiletsFee)}</strong></div>`);
            if (chkWin && chkWin.checked) lines.push(`<div>Вікна (${safeNum(sqmWin ? sqmWin.value : 1, 1)} м² × ₴${safeNum(rateWin ? rateWin.value : 240, 240)}): <strong>${fmt(windowsTotal)}</strong></div>`);
            if (chkBal && chkBal.checked) lines.push(`<div>Балкон (${safeInt(qtyBal ? qtyBal.value : 1, 1)} × ₴${cfg.balcony}): <strong>${fmt(Math.max(1, safeInt(qtyBal ? qtyBal.value : 1, 1)) * cfg.balcony)}</strong></div>`);
            if (chkLog && chkLog.checked) lines.push(`<div>Лоджія (${safeInt(qtyLog ? qtyLog.value : 1, 1)} × ₴${cfg.loggia}): <strong>${fmt(Math.max(1, safeInt(qtyLog ? qtyLog.value : 1, 1)) * cfg.loggia)}</strong></div>`);
            extraLines.forEach(o => lines.push(`<div>${o.label}: <strong>${fmt(o.price)}</strong></div>`));
            if (KM > 0) lines.push(`<div>Виїзд за місто (${KM} км × ₴${travelRate}/км): <strong>${fmt(travelFee)}</strong></div>`);
            if (chkLoyal && chkLoyal.checked) lines.push(`<div>Знижка 5-те прибирання (−15%): <strong>−${fmt(loyalCut)}</strong></div>`);
            if (appliedMin) lines.push(`<div><strong>Мінімальне замовлення</strong>: доведено до <strong>${fmt(2000)}</strong>.</div>`);

            breakdown.innerHTML = lines.join('');
            
            // Store current total for submission
            window.currentTotal = fmt(Math.max(0, totalUAH));
            window.currentBreakdown = lines.join('\n').replace(/<[^>]*>/g, ''); // Strip HTML for plain text
        }
    }

    // ОБНОВЛЕННАЯ ФУНКЦИЯ ОТПРАВКИ
    async function submitOrder() {
        console.log('Submit button clicked'); // Для отладки
        
        const name = clientName ? clientName.value.trim() : '';
        const phone = clientPhone ? clientPhone.value.trim() : '';
        
        if (!name) {
            showMessage('Будь ласка, введіть ваше ім\'я', true);
            if (clientName) clientName.focus();
            return;
        }
        
        if (name.length < 2) {
            showMessage('Ім\'я повинно містити мінімум 2 символи', true);
            if (clientName) clientName.focus();
            return;
        }
        
        if (!phone) {
            showMessage('Будь ласка, введіть номер телефону', true);
            if (clientPhone) clientPhone.focus();
            return;
        }
        
        const phoneValidation = validatePhone(phone);
        if (!phoneValidation.valid) {
            showMessage(phoneValidation.message, true);
            if (clientPhone) clientPhone.focus();
            return;
        }
        
        if (cta) {
            cta.disabled = true;
            cta.textContent = 'Відправляємо...';
        }
        
        try {
            const formData = collectFormData();
            formData.total = window.currentTotal || '₴2000';
            formData.breakdown = window.currentBreakdown || '';
            
            // Добавляем информацию о странице
            if (typeof crystal_ajax !== 'undefined') {
                formData.pageUrl = crystal_ajax.page_url || window.location.href;
                formData.pageTitle = crystal_ajax.page_title || document.title;
            } else {
                formData.pageUrl = window.location.href;
                formData.pageTitle = document.title;
            }
            
            console.log('Sending data:', formData); // Для отладки
            
            const response = await $.post({
                url: crystal_ajax.ajax_url,
                data: {
                    action: 'crystal_send_telegram',
                    nonce: crystal_ajax.nonce,
                    formData: JSON.stringify(formData)
                }
            });
            
            console.log('Response received:', response); // Для отладки
            
            if (response.success) {
                showMessage(response.data || 'Заявка успішно відправлена! Ми зв\'яжемося з вами найближчим часом.');
                if (clientName) clientName.value = '';
                if (clientPhone) clientPhone.value = '';
                if (phoneError) phoneError.style.display = 'none';
                if (clientPhone) clientPhone.style.borderColor = '#d1d5db';
            } else {
                showMessage('Помилка: ' + (response.data || 'Невідома помилка'), true);
            }
        } catch (error) {
            console.error('AJAX Error:', error); // Для отладки
            showMessage('Помилка відправки. Спробуйте пізніше.', true);
        } finally {
            if (cta) {
                cta.disabled = false;
                cta.textContent = 'Залишити заявку';
            }
        }
    }

    // Event listeners
    [pkg, area, soil, currency, eurRate, baths, toilets, distance, chkLoyal].forEach(x => {
        if (x) x.addEventListener('input', calc);
    });
    
    if (pkg) pkg.addEventListener('change', () => { refreshRateSelector(); calc(); });
    if (rateSel) rateSel.addEventListener('change', calc);
    if (rateInput) rateInput.addEventListener('input', calc);
    
    if (chkWin) chkWin.addEventListener('change', () => { toggle(sqmWin, chkWin.checked); toggle(rateWin, chkWin.checked); calc(); });
    [sqmWin, rateWin].forEach(x => { if (x) x.addEventListener('input', calc); });
    
    if (chkBal) chkBal.addEventListener('change', () => { toggle(qtyBal, chkBal.checked); calc(); });
    if (chkLog) chkLog.addEventListener('change', () => { toggle(qtyLog, chkLog.checked); calc(); });
    [qtyBal, qtyLog].forEach(x => { if (x) x.addEventListener('input', calc); });
    
    if (chkWalls) chkWalls.addEventListener('change', () => { toggleQty(chkWalls, qtyWalls, lblWalls); calc(); });
    if (chkIron) chkIron.addEventListener('change', () => { toggleQty(chkIron, qtyIron, lblIron); calc(); });
    if (chkDishes) chkDishes.addEventListener('change', () => { toggleQty(chkDishes, qtyDishes, lblDishes); calc(); });
    if (chkLaundry) chkLaundry.addEventListener('change', () => { toggleQty(chkLaundry, qtyLaundry, lblLaundry); calc(); });
    if (chkKC) chkKC.addEventListener('change', () => { toggleQty(chkKC, qtyKC, lblKC); calc(); });
    if (chkOrg) chkOrg.addEventListener('change', () => { toggleQty(chkOrg, qtyOrg, lblOrg); calc(); });
    if (chkCurt) chkCurt.addEventListener('change', () => { toggle(amtCurt, chkCurt.checked); calc(); });
    
    [qtyWalls, qtyIron, qtyDishes, qtyLaundry, qtyKC, qtyAB, qtyAT, qtyOrg, amtCurt].forEach(x => {
        if (x) x.addEventListener('input', calc);
    });
    
    [chkPet, chkHood, chkOven, chkMW, chkFridge, chkGrill, chkAB, chkAT, chkVac, chkKeys].forEach(x => {
        if (x) x.addEventListener('change', calc);
    });
    
    // Chemical cleaning inputs
    ['chem_sofa2', 'chem_sofa3', 'chem_sofa4', 'chem_sofa5', 'chem_sofa6',
     'chem_armchair', 'chem_mattress2', 'chem_mattress1', 'chem_chair', 
     'chem_headboard', 'chem_pillow_qty', 'chem_pillow_rate'].forEach(id => {
        const element = el(id);
        if (element) element.addEventListener('input', calc);
    });
    
    // Submit button
    if (cta) cta.addEventListener('click', submitOrder);
    
    // Initialize
    function initToggles() {
        refreshRateSelector();
        toggle(sqmWin, false);
        toggle(rateWin, false);
        toggle(qtyBal, false);
        toggle(qtyLog, false);
        toggle(amtCurt, false);
        toggleQty(chkWalls, qtyWalls, lblWalls);
        toggleQty(chkIron, qtyIron, lblIron);
        toggleQty(chkDishes, qtyDishes, lblDishes);
        toggleQty(chkLaundry, qtyLaundry, lblLaundry);
        toggleQty(chkKC, qtyKC, lblKC);
        toggleQty(chkOrg, qtyOrg, lblOrg);
    }
    
    initToggles();
    calc();
});
