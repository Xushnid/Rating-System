/**
 * Foydalanuvchiga xabar ko'rsatish funksiyasi.
 * @param {string} message - Xabar matni.
 * @param {string} type - Xabar turi ('success', 'danger', 'warning', 'info').
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer');
    if (!alertContainer) {
        console.error('Xabarlar uchun "alertContainer" ID li element topilmadi.');
        return;
    }
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.role = 'alert';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    alertContainer.innerHTML = ''; // Avvalgi xabarlarni tozalash
    alertContainer.appendChild(alert);

    // Bir necha soniyadan so'ng avtomatik yopish
    setTimeout(() => {
        const alertInstance = bootstrap.Alert.getOrCreateInstance(alert);
        if (alertInstance) {
            alertInstance.close();
        }
    }, 5000);
}




/**
 * Qalqib chiquvchi yordam (Tooltips) oynalarini ishga tushirish.
 * Bu funksiya AJAX orqali yangi kontent kelganda ham chaqiriladi.
 */
function initTooltips() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        // Avvalgi tooltipni o'chirish (agar bo'lsa)
        var tooltipInstance = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
        if (tooltipInstance) {
            tooltipInstance.dispose();
        }
        // Yangisini yaratish
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}
/**
 * Sana maydonini tekshirish.
 * @param {HTMLInputElement} input - Sana kiritilgan input elementi.
 * @param {HTMLElement} errorElement - Xatolik matni chiqariladigan element.
 * @returns {boolean} - Sana to'g'ri bo'lsa true, aks holda false.
 */
function validateDate(input, errorElement) {
    if (!input || !errorElement) {
        console.warn('validateDate: input yoki errorElement topilmadi');
        return true; // Elementlar yo'q bo'lsa, tekshirmaslik
    }

    const date = input.value;
    console.log('Validating date:', date, 'Required:', input.required);
    
    if (input.required && !date) {
        errorElement.textContent = 'Sana kiritilishi shart.';
        errorElement.style.display = 'block';
        input.classList.add('is-invalid');
        return false;
    }
    
    if (date) {
        const inputDate = new Date(date);
        const today = new Date();
        today.setHours(23, 59, 59, 999); // Set to end of today for comparison
        
        if (inputDate > today) {
            errorElement.textContent = 'Sana kelajakda bo\'lishi mumkin emas.';
            errorElement.style.display = 'block';
            input.classList.add('is-invalid');
            return false;
        }
    }
    
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    input.classList.remove('is-invalid');
    return true;
}

/**
 * Fayl maydonini tekshirish.
 * @param {HTMLInputElement} input - Fayl kiritilgan input elementi.
 * @param {HTMLElement} errorElement - Xatolik matni chiqariladigan element.
 * @param {number} maxSize - Faylning baytlardagi maksimal hajmi.
 * @returns {boolean} - Fayl to'g'ri bo'lsa true, aks holda false.
 */
function validateFile(input, errorElement, maxSize) {
    if (!input || !errorElement) {
        console.warn('validateFile: input yoki errorElement topilmadi');
        return true;
    }

    const file = input.files[0];
    console.log('Validating file:', file, 'Required:', input.required);
    
    if (input.required && !file) {
        errorElement.textContent = 'Fayl yuklanishi shart.';
        errorElement.style.display = 'block';
        input.classList.add('is-invalid');
        return false;
    }
    
    if (file) {
        console.log('File type:', file.type, 'Size:', file.size);
        
        if (file.type !== 'application/pdf') {
            errorElement.textContent = 'Faqat PDF formatidagi fayllar qabul qilinadi.';
            errorElement.style.display = 'block';
            input.classList.add('is-invalid');
            return false;
        }
        
        if (file.size > maxSize) {
            const maxSizeInMB = (maxSize / (1024 * 1024)).toFixed(1);
            errorElement.textContent = `Fayl hajmi ${maxSizeInMB}MB dan katta bo'lmasligi kerak.`;
            errorElement.style.display = 'block';
            input.classList.add('is-invalid');
            return false;
        }
    }
    
    errorElement.textContent = '';
    errorElement.style.display = 'none';
    input.classList.remove('is-invalid');
    return true;
}

/**
 * Mualliflar soniga qarab ulushni avtomatik hisoblash.
 * @param {string} authorsCountId - Mualliflar soni kiritiladigan inputning IDsi.
 * @param {string} shareId - Ulush ko'rsatiladigan inputning IDsi.
 */
function calculateShare(authorsCountId, shareId) {
    const authorsCountInput = document.getElementById(authorsCountId);
    const shareInput = document.getElementById(shareId);

    if (authorsCountInput && shareInput) {
        const count = parseInt(authorsCountInput.value, 10);
        if (count && count > 0) {
            shareInput.value = (1 / count).toFixed(3);
        } else {
            shareInput.value = (1).toFixed(3); // Agar 0 yoki noto'g'ri qiymat bo'lsa
        }
    }
}