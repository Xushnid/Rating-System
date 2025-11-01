<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Ishlanma qo'shish - Rating System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../partials/header.php'; ?>
    <main class="container py-4">
        <div class="page-header">
            <h2 class="h4 page-title"><i class="bi bi-plus-circle"></i>Yangi Ilmiy Ishlanma Qo'shish</h2>
        </div>
        
        <div id="alertContainer"></div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                 <div class="card">
                    <div class="card-body p-4 p-md-5">
                        <form id="addIshlanmaForm" novalidate enctype="multipart/form-data">
                            <input type="hidden" name="user_id" value="<?= $currentUser['id']; ?>">
                            <input type="hidden" name="full_name" value="<?= htmlspecialchars($currentUser['full_name']); ?>">
                            
                            <div class="mb-4">
                                <label for="section" class="form-label fs-5">1. Bo'limni tanlang</label>
                                <select id="section" name="section" class="form-select form-select-lg" required>
                                    <option value="" selected disabled>--- Bo'limni tanlang ---</option>
                                    <?php foreach ($section_groups as $group): ?>
                                        <optgroup label="<?= htmlspecialchars($group['name']); ?>">
                                            <?php foreach ($group['sections'] as $code): 
                                                $section = $sections[$code];
                                            ?>
                                                <option value="<?= $code; ?>"><?= htmlspecialchars($code . ' - ' . $section['name']); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
							<div class="mb-4">
                                <label for="period_id" class="form-label fs-5">2. Davrni tanlang</label>
                                <select id="period_id" name="period_id" class="form-select form-select-lg" required>
                                    <option value="" selected disabled>--- Davrni tanlang ---</option>
                                    <?php foreach ($activePeriods as $period): ?>
                                        <option value="<?= $period['id']; ?>"><?= htmlspecialchars($period['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div id="dynamic-form-fields" style="display: none;">
                                <hr class="my-4">
                                <h5 class="mb-3">3. Kerakli ma'lumotlarni to'ldiring</h5>
                                <div id="sectionSpecificFields"></div>
                                <div class="common-fields" style="display: none;">
                                     <div class="mb-3">
                                        <label for="article_name" class="form-label">Maqola/Ishlanma nomi *</label>
                                        <input type="text" id="article_name" name="article_name" class="form-control" maxlength="255">
                                    </div>
                                    <div class="mb-3">
                                         <label for="publish_date" class="form-label">Nashr sanasi *</label>
                                        <input type="date" id="publish_date" name="publish_date" class="form-control">
                                        <div id="date-error" class="validation-error"></div>
                                    </div>
                                    <div class="mb-3">
                                         <label for="url" class="form-label">Manba Link (ixtiyoriy)</label>
                                        <input type="url" id="url" name="url" class="form-control" maxlength="255">
                                    </div>
                                     <div class="row">
                                        <div class="col-md-6 mb-3">
                                             <label for="authors_count" class="form-label">Mualliflar soni *</label>
                                            <input type="number" id="authors_count" name="authors_count" class="form-control" min="1" value="1">
                                         </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="share" class="form-label">Ulush (avtomatik)</label>
                                            <input type="number" id="share" name="share" class="form-control" step="0.001" readonly>
                                        </div>
                                     </div>
                                </div>
                                
                                 <div class="file-field" style="display: none;">
                                    <hr>
                                    <h5 class="mb-3">4. Faylni yuklang</h5>
                                    <div class="mb-3">
                                        <label for="file" class="form-label">Fayl (PDF, maks. 1.5MB) *</label>
                                         <input type="file" id="file" name="file" class="form-control" accept="application/pdf">
                                        <div id="file-error" class="validation-error"></div>
                                     </div>
                                </div>

                                <div class="text-center mt-4 pt-3 border-top">
                                     <button type="submit" id="submitBtn" class="btn btn-primary btn-lg px-5">
                                        <i class="bi bi-send me-2"></i>Yuborish
                                    </button>
                                 </div>
                            </div>
							<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($auth->generateCsrfToken()) ?>">
</form>
                    </div>
                </div>
             </div>
        </div>
    </main>

   <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/script.js"></script>
    <script>
        const sections = <?= json_encode($sections) ?>;

        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('addIshlanmaForm');
            const sectionSelect = document.getElementById('section');
            const dynamicFieldsContainer = document.getElementById('dynamic-form-fields');
            const sectionFields = document.getElementById('sectionSpecificFields');
            const commonFields = document.querySelector('.common-fields');
            const fileField = document.querySelector('.file-field');
            const submitBtn = document.getElementById('submitBtn');

            // Maydon yaratish uchun yordamchi funksiya
            function createField(name, config, container) {
                const div = document.createElement('div');
                div.className = 'mb-3';
                const label = document.createElement('label');
                label.htmlFor = name;
                label.className = 'form-label';
                label.textContent = config.label + (config.required ? ' *' : '');
                div.appendChild(label);
                
                let input;
                if (config.type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 3;
                } else {
                    input = document.createElement('input');
                    input.type = config.type;
                }
                
                input.id = name;
                input.name = name;
                input.className = 'form-control';
                if (config.required) input.required = true;
                
                div.appendChild(input);

                if (config.type === 'date') {
                    const errorDiv = document.createElement('div');
                    errorDiv.id = `${name}-error`;
                    errorDiv.className = 'validation-error';
                    div.appendChild(errorDiv);
                    input.addEventListener('input', () => validateDate(input, errorDiv));
                }
                
                container.appendChild(div);

                // ### TUZATISH MANA SHU YERDA ###
                // Agar dinamik "Mualliflar soni" maydoni yaratilsa, unga kalkulyator funksiyasini biriktiramiz.
                if (name === 'authors_count') {
                    if(!input.value) input.value = 1; 
                    input.addEventListener('input', () => calculateShare(name, 'share'));
                }
                // Agar dinamik "Ulush" maydoni yaratilsa, uni faqat o'qish uchun (readonly) qilamiz.
                if (name === 'share') {
                    input.readOnly = true;
                    // Boshlang'ich qiymatni hisoblab qo'yamiz
                    calculateShare('authors_count', 'share');
                }
            }

            // 4.4 BO'LIMI UCHUN MAXSUS MANTIQ
            function handleAcademicExchange() {
                sectionFields.innerHTML = ''; // Tozalash

                const typeSelectorDiv = document.createElement('div');
                typeSelectorDiv.className = 'mb-3';
                typeSelectorDiv.innerHTML = `
                    <label for="exchange_type" class="form-label">Almashuv dasturi turini tanlang *</label>
                    <select id="exchange_type" name="exchange_type" class="form-select" required>
                        <option value="" selected disabled>--- Tanlang ---</option>
                        <option value="sent">Jo'natilgan talaba</option>
                        <option value="received">Xorijlik talaba</option>
                    </select>`;
                sectionFields.appendChild(typeSelectorDiv);

                const conditionalFieldsContainer = document.createElement('div');
                sectionFields.appendChild(conditionalFieldsContainer);

                const typeSelector = document.getElementById('exchange_type');
                typeSelector.addEventListener('change', () => {
                    conditionalFieldsContainer.innerHTML = ''; // Har tanlovda tozalash
                    const type = typeSelector.value;
                    if (!type) return;

                    const commonFieldsSet = {
                        'basis_document': { label: 'Almashuv dasturiga asos bo‘luvchi hujjat nomi va imzolangan sanasi', type: 'text', required: true },
                        'country': { label: 'Davlat', type: 'text', required: true },
                        'university_name': { label: 'OTM nomi', type: 'text', required: true },
                        'specialty_name': { label: 'Ta’lim yo‘nalishi (mutaxassislik) nomi', type: 'text', required: true }
                    };

                    let studentFieldLabel = '';
                    if (type === 'sent') {
                        studentFieldLabel = 'Xorijiy OTMda ta’lim olayotgan talabaning F.I.Sh';
                    } else { // received
                        studentFieldLabel = 'OTMda ta’lim olayotgan xorijlik talabaning F.I.Sh';
                    }
                    
                    const fieldsToShow = {
                        'basis_document': commonFieldsSet.basis_document,
                        'student_name': { label: studentFieldLabel, type: 'text', required: true },
                        'country': commonFieldsSet.country,
                        'university_name': commonFieldsSet.university_name,
                        'specialty_name': commonFieldsSet.specialty_name,
                    };

                    for (const [name, config] of Object.entries(fieldsToShow)) {
                        createField(name, config, conditionalFieldsContainer);
                    }
                });
            }

            // ASOSIY EVENT LISTENER
            sectionSelect.addEventListener('change', (e) => {
                const sectionKey = e.target.value;
                if (!sectionKey) {
                    dynamicFieldsContainer.style.display = 'none';
                    return;
                }

                dynamicFieldsContainer.style.display = 'block';
                sectionFields.innerHTML = '';
                const config = sections[sectionKey];

                // 4.4 uchun maxsus ishlovchini chaqirish
                if (sectionKey === '4.4') {
                    handleAcademicExchange();
                } else {
                    // Boshqa barcha bo'limlar uchun standart mantiq
                    for (const [field, fieldConfig] of Object.entries(config.fields)) {
                        createField(field, fieldConfig, sectionFields);
                    }
                }
                
                const commonFieldsInputs = commonFields.querySelectorAll('input, select, textarea');
                if (config.has_common_fields) {
                    commonFields.style.display = 'block';
                    commonFieldsInputs.forEach(input => {
                        input.disabled = false;
                        input.required = (input.id === 'article_name' || input.id === 'publish_date' || input.id === 'authors_count');
                    });
                    calculateShare('authors_count', 'share');
                } else {
                    commonFields.style.display = 'none';
                    commonFieldsInputs.forEach(input => {
                        input.disabled = true;
                        input.required = false;
                    });
                }
                
                const fileInput = document.getElementById('file');
                if (config.has_file) {
                    fileField.style.display = 'block';
                    fileInput.disabled = false;
                    fileInput.required = true;
                } else {
                    fileField.style.display = 'none';
                    fileInput.disabled = true;
                    fileInput.required = false;
                }
            });
            
            // Umumiy maydonlar uchun event listenerlar (faqat bir marta biriktiriladi)
            document.getElementById('publish_date').addEventListener('input', () => validateDate(document.getElementById('publish_date'), document.getElementById('date-error')));
            document.getElementById('authors_count').addEventListener('input', () => calculateShare('authors_count', 'share'));
            
            // Formani yuborish
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                let isValid = form.checkValidity();
                form.classList.add('was-validated');
                
                form.querySelectorAll('input[type="date"]:not(:disabled)').forEach(dateInput => {
                     const errorDiv = document.getElementById(`${dateInput.id}-error`);
                    if (errorDiv && !validateDate(dateInput, errorDiv)) isValid = false;
                 });

                const fileInput = document.getElementById('file');
                if (!fileInput.disabled) {
                    if (fileInput.files.length > 0) {
                        if (!validateFile(fileInput, document.getElementById('file-error'), 1.5 * 1024 * 1024)) isValid = false;
                    } else if (fileInput.required) {
                        isValid = false;
                        document.getElementById('file-error').textContent = 'Fayl yuklanishi shart.';
                    }
                }

                if (!isValid) {
                    showAlert('Iltimos, barcha majburiy maydonlarni to\'g\'ri to\'ldiring!', 'danger');
                    return;
                }

                const originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Yuborilmoqda...`;
                try {
                    const formData = new FormData(form);
                    const response = await fetch('/ajax/user/submission/create', { method: 'POST', body: formData });
                    const result = await response.json();
                    showAlert(result.message, result.success ? 'success' : 'danger');
                    if (result.success) {
                        form.reset();
                        form.classList.remove('was-validated');
                        dynamicFieldsContainer.style.display = 'none';
                    }
                } catch (error) {
                    showAlert('Server bilan bog\'lanishda kutilmagan xatolik yuz berdi.', 'danger');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });
    </script>
</body>
</html>