class IshlanmaManager {
    constructor(modalId, pageContext = 'user') {
        this.pageContext = pageContext; // 'admin', 'department-admin' yoki 'user'
        const modalEl = document.getElementById(modalId);
        if (modalEl) {
            this.modal = new bootstrap.Modal(modalEl);
            this.form = document.getElementById('editIshlanmaForm');
            this.initFormEventListeners();
        } else {
            this.modal = null;
            this.form = null;
        }

        // Rad etish oynasi uchun yangi xususiyatlar
        const rejectionModalEl = document.getElementById('rejectionReasonModal');
        if (rejectionModalEl) {
            this.rejectionModal = new bootstrap.Modal(rejectionModalEl);
            this.rejectionForm = document.getElementById('rejectionReasonForm');
            this.initRejectionFormListener();
        }

        this.initGlobalEventListeners();
    }

    // AJAX so'rovlari uchun to'g'ri yo'lni (route) qaytaradi
    getRoute(action) {
        const base = `/ajax/${this.pageContext}/submission`;
        const routes = {
            'get': `${base}/get`,
            'update': `${base}/update`,
            'delete': `${base}/delete`,
            'status': `${base}/status`
        };
        return routes[action];
    }

    initGlobalEventListeners() {
        document.addEventListener('click', (e) => {
            const editBtn = e.target.closest('.edit-ishlanma-btn');
            const deleteBtn = e.target.closest('.delete-ishlanma-btn');
            const approveBtn = e.target.closest('.approve-ishlanma-btn');
            const rejectBtn = e.target.closest('.reject-ishlanma-btn');

            if (editBtn && this.modal) {
                e.preventDefault();
                this.loadEditForm(editBtn.dataset.id);
            } else if (deleteBtn) {
                e.preventDefault();
                this.deleteIshlanma(deleteBtn.dataset.id);
            } else if (approveBtn) {
                e.preventDefault();
                this.updateStatus(approveBtn.dataset.id, 'approved');
            } else if (rejectBtn && this.rejectionModal) {
                // O'ZGARISH: Endi bu tugma modal oynani ochadi
                e.preventDefault();
                this.openRejectionModal(rejectBtn.dataset.id);
            }
        });
    }
    
    // Rad etish oynasi uchun yangi metod
    openRejectionModal(id) {
        this.rejectionForm.reset();
        this.rejectionForm.classList.remove('was-validated');
        document.getElementById('rejection_submission_id').value = id;
        this.rejectionModal.show();
    }
    
    // Rad etish formasini eshituvchi yangi metod
    initRejectionFormListener() {
            if (!this.rejectionForm) return;

            this.rejectionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                if (!this.rejectionForm.checkValidity()) {
                    e.stopPropagation();
                    this.rejectionForm.classList.add('was-validated');
                    return;
                }
                
                const id = document.getElementById('rejection_submission_id').value;
                const reason = document.getElementById('rejection_reason_text').value;
                
                this.updateStatus(id, 'rejected', reason);
                
                // Yopishdan oldin fokusni olib tashlash
                const focusedElement = document.activeElement;
                if(focusedElement) {
                    focusedElement.blur();
                }
                
                this.rejectionModal.hide();
            });
        }

    initFormEventListeners() {
        if (!this.form) return;
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitEditForm();
        });
        const authorsCountInput = document.getElementById('edit_authors_count');
        if (authorsCountInput) {
            authorsCountInput.addEventListener('input', () => {
                calculateShare('edit_authors_count', 'edit_share');
            });
        }
    }

    async loadEditForm(id) {
        if (!this.modal) return;
        try {
            const url = `${this.getRoute('get')}?id=${id}`;
            console.log('Loading edit form for ID:', id, 'URL:', url);
            
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Edit form data received:', result);
            
            if (!result.success) {
                showAlert(result.message, 'danger');
                return;
            }

            const item = result.data;
            const sectionConfig = sections[item.section_code];
            
            if (!sectionConfig) {
                showAlert('Bo\'lim konfiguratsiyasi topilmadi', 'danger');
                return;
            }

            // Reset form
            this.form.reset();
            this.form.classList.remove('was-validated');
            document.getElementById('editSectionSpecificFields').innerHTML = '';
            
            // Remove all validation classes
            this.form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            this.form.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));

            // Debug logging for section 2.6.1
            if (item.section_code === '2.6.1') {
                console.log('=== Section 2.6.1 Edit Form Debug ===');
                console.log('Item data:', item.data);
                console.log('Section config:', sectionConfig);
                console.log('has_common_fields:', sectionConfig.has_common_fields);
                console.log('Section fields:', Object.keys(sectionConfig.fields));
            }

            // Create section-specific fields FIRST
            for (const [field, config] of Object.entries(sectionConfig.fields)) {
                const fieldValue = item.data[field] || '';
                this.createFormField(`edit_${field}`, field, fieldValue, config);
                
                if (item.section_code === '2.6.1') {
                    console.log(`Created section field '${field}' with value: '${fieldValue}'`);
                }
            }
            
            // IMPORTANT: Ensure all section-specific fields are enabled and editable
            const sectionSpecificInputs = document.querySelectorAll('#editSectionSpecificFields input, #editSectionSpecificFields textarea, #editSectionSpecificFields select');
            sectionSpecificInputs.forEach(input => {
                input.disabled = false;
                input.readOnly = false;
                console.log(`Enabled section-specific field: ${input.name}`);
            });
            
            // Handle common fields
            const commonFieldsContainer = this.form.querySelector('.edit-common-fields');
            const articleNameInput = document.getElementById('edit_article_name');
            const publishDateInput = document.getElementById('edit_publish_date');
            const authorsCountInput = document.getElementById('edit_authors_count');
            const shareInput = document.getElementById('edit_share');
            const urlInput = document.getElementById('edit_url');

            if (sectionConfig.has_common_fields) {
                commonFieldsContainer.style.display = 'block';
                
                if (articleNameInput) {
                    articleNameInput.value = item.data.article_name || '';
                    articleNameInput.required = true;
                }
                if (publishDateInput) {
                    publishDateInput.value = item.data.publish_date || '';
                    publishDateInput.required = true;
                    publishDateInput.addEventListener('input', () => {
                        const errorDiv = document.getElementById('edit_date-error');
                        if (errorDiv) validateDate(publishDateInput, errorDiv);
                    });
                }
                if (urlInput) {
                    urlInput.value = item.data.url || '';
                }
                if (authorsCountInput) {
                    const authorsCount = item.data.authors_count || 1;
                    authorsCountInput.value = authorsCount;
                    authorsCountInput.required = true;
                    
                    // Calculate and set share
                    if (shareInput) {
                        shareInput.value = (1 / (authorsCount || 1)).toFixed(3);
                    }
                    
                    // Add event listener for share calculation
                    authorsCountInput.addEventListener('input', () => {
                        calculateShare('edit_authors_count', 'edit_share');
                    });
                }
            } else {
                commonFieldsContainer.style.display = 'none';
                
                // CRITICAL FIX: For sections without common fields, disable ONLY common field inputs
                // to prevent them from being included in form submission, but keep section-specific fields enabled
                const commonFieldInputs = commonFieldsContainer.querySelectorAll('input, textarea, select');
                commonFieldInputs.forEach(input => {
                    input.disabled = true; // Disable only common field inputs
                    input.required = false;
                });
                
                // IMPORTANT: For section 2.6.1, the section-specific fields should be used
                // They were already created above and will override any common field behavior
                if (item.section_code === '2.6.1') {
                    console.log('Section 2.6.1 uses section-specific fields, not common fields');
                    // Verify that the problematic fields are properly populated
                    const problemFields = ['publish_date', 'article_name', 'url'];
                    problemFields.forEach(fieldName => {
                        const fieldElement = document.getElementById(`edit_${fieldName}`);
                        if (fieldElement) {
                            const expectedValue = item.data[fieldName] || '';
                            console.log(`Field '${fieldName}': Element found, value = '${fieldElement.value}', expected = '${expectedValue}'`);
                            // Ensure the value is set correctly
                            if (fieldElement.value !== expectedValue) {
                                console.warn(`Correcting field '${fieldName}' value from '${fieldElement.value}' to '${expectedValue}'`);
                                fieldElement.value = expectedValue;
                            }
                            // IMPORTANT: Ensure section-specific fields are NOT disabled
                            fieldElement.disabled = false;
                            fieldElement.readOnly = false;
                        } else {
                            console.error(`Field element 'edit_${fieldName}' not found!`);
                        }
                    });
                }
            }
            
            // Handle file field
            const fileFieldContainer = this.form.querySelector('.edit-file-field');
            const fileInput = document.getElementById('edit_file');

            if (sectionConfig.has_file) {
                fileFieldContainer.style.display = 'block';
                if (fileInput) {
                    fileInput.required = false; // File is optional in edit mode
                }
            } else {
                fileFieldContainer.style.display = 'none';
                if (fileInput) {
                    fileInput.required = false;
                }
            }
            
            // Set the ID for update
            document.getElementById('edit_ishlanma_id').value = item.id;
            
            // Show modal
            this.modal.show();
            
            console.log('Edit form loaded successfully');
        } catch (error) {
            console.error('Tahrirlash formasini yuklashda xatolik:', error);
            showAlert('Server bilan bog\'lanishda xatolik. Iltimos, qayta urinib ko\'ring.', 'danger');
        }
    }
    
    createFormField(id, name, value, config) {
        const container = document.getElementById('editSectionSpecificFields');
        const div = document.createElement('div');
        div.className = 'mb-3';
        
        const label = document.createElement('label');
        label.htmlFor = id;
        label.className = 'form-label';
        label.textContent = config.label + (config.required ? ' *' : '');
        div.appendChild(label);
        
        let input;
        if (config.type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = 3;
            input.textContent = value; // Use textContent for textarea
        } else {
            input = document.createElement('input');
            input.type = config.type;
            input.value = value;
        }
        
        input.id = id;
        input.name = name;
        input.className = 'form-control';
        
        // IMPORTANT: For section 2.6.1, add a data attribute to identify section-specific fields
        // This helps distinguish them from common fields with the same names
        input.setAttribute('data-section-specific', 'true');
        
        // Ensure section-specific fields are always enabled and editable
        input.disabled = false;
        input.readOnly = false;
        
        // Set validation attributes
        if (config.required) {
            input.required = true;
            input.setAttribute('aria-required', 'true');
        }
        
        // Add specific validation for different field types
        if (config.type === 'email') {
            input.setAttribute('pattern', '[a-z0-9._%+-]+@[a-z0-9.-]+\\.[a-z]{2,}$');
        }
        if (config.type === 'url') {
            input.setAttribute('pattern', 'https?://.+');
        }
        if (config.type === 'number') {
            input.setAttribute('min', '0');
            input.setAttribute('step', 'any');
        }
        
        div.appendChild(input);
        
        // Add validation error div for special field types
        if (config.type === 'date' || config.type === 'file') {
            const errorDiv = document.createElement('div');
            errorDiv.id = `${id}-error`;
            errorDiv.className = 'validation-error text-danger small';
            div.appendChild(errorDiv);
            
            if (config.type === 'date') {
                input.addEventListener('input', () => validateDate(input, errorDiv));
                input.addEventListener('blur', () => validateDate(input, errorDiv));
            }
        }
        
        container.appendChild(div);
    }

    async submitEditForm() {
        if (!this.form) return;
        
        // Reset all validation states
        this.form.classList.remove('was-validated');
        let isValid = true;
        let validationErrors = [];
        
        // Check basic HTML5 validation first
        const formValidity = this.form.checkValidity();
        if (!formValidity) {
            this.form.classList.add('was-validated');
        }
        
        // Manually validate all required fields
        const requiredFields = this.form.querySelectorAll('input[required], textarea[required], select[required]');
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                validationErrors.push(`${field.previousElementSibling.textContent}: Bu maydon to'ldirilishi shart`);
                field.classList.add('is-invalid');
            } else {
                field.classList.remove('is-invalid');
            }
        });
        
        // Validate date fields
        this.form.querySelectorAll('input[type="date"]').forEach(dateInput => {
            const errorDiv = dateInput.parentElement.querySelector('.validation-error');
            if (errorDiv && !validateDate(dateInput, errorDiv)) {
                isValid = false;
                validationErrors.push('Sana noto\'g\'ri kiritilgan');
            }
        });
        
        // Validate file input if present
        const fileInput = this.form.querySelector('#edit_file');
        if (fileInput && fileInput.files.length > 0) {
            const fileErrorDiv = document.getElementById('edit_file-error');
            if (fileErrorDiv && !validateFile(fileInput, fileErrorDiv, 1.5 * 1024 * 1024)) {
                isValid = false;
                validationErrors.push('Yuklangan fayl noto\'g\'ri');
            }
        }
        
        // Show specific validation errors if any
        if (!isValid) {
            const errorMessage = validationErrors.length > 0 
                ? validationErrors.join('; ') 
                : 'Iltimos, barcha majburiy maydonlarni to\'g\'ri to\'ldiring!';
            showAlert(errorMessage, 'warning');
            return;
        }

        const submitBtn = document.getElementById('editSubmitBtn');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saqlanmoqda...`;
        
        try {
            const formData = new FormData(this.form);
            
            // CRITICAL FIX: Handle field name conflicts for sections without common fields
            // For sections like 2.6.1 that use common field names but have has_common_fields: false,
            // we need to ensure section-specific fields override any common field values
            
            const sectionSpecificFields = this.form.querySelectorAll('#editSectionSpecificFields input[data-section-specific="true"], #editSectionSpecificFields textarea[data-section-specific="true"]');
            
            if (sectionSpecificFields.length > 0) {
                console.log('Found section-specific fields, checking for conflicts...');
                
                // Remove conflicting common field values and use section-specific values
                sectionSpecificFields.forEach(field => {
                    const fieldName = field.name;
                    const fieldValue = field.value;
                    
                    // Remove any existing entries for this field name
                    formData.delete(fieldName);
                    
                    // Add the section-specific field value
                    formData.append(fieldName, fieldValue);
                    
                    console.log(`Field conflict resolved: '${fieldName}' = '${fieldValue}' (from section-specific field)`);
                });
            }
            
            // Debug: Log form data with special attention to section 2.6.1
            console.log('Form data being sent:');
            const submissionId = formData.get('id');
            console.log('Submission ID:', submissionId);
            
            const dataEntries = [];
            for (let [key, value] of formData.entries()) {
                dataEntries.push({key, value});
                console.log(`${key}: "${value}"`);
            }
            
            // Check if this is section 2.6.1 and validate specific fields
            const sectionSpecificFieldsDebug = this.form.querySelectorAll('#editSectionSpecificFields input, #editSectionSpecificFields textarea');
            if (sectionSpecificFieldsDebug.length > 0) {
                console.log('Section-specific fields in form:');
                sectionSpecificFieldsDebug.forEach(field => {
                    console.log(`Field ${field.name}: "${field.value}" (required: ${field.required})`);
                });
            }
            
            const url = this.getRoute('update');
            console.log('Sending request to:', url);
            
            const response = await fetch(url, { 
                method: 'POST', 
                body: formData 
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Server response:', result);
            
            showAlert(result.message, result.success ? 'success' : 'danger');

            if (result.success) {
                this.modal.hide();
                if (typeof window.refreshTable === 'function') {
                    window.refreshTable();
                } else {
                    location.reload();
                }
            }
        } catch (error) {
            console.error('Tahrirlash formasini yuborishda xatolik:', error);
            showAlert('Server bilan bog\'lanishda xatolik. Iltimos, qayta urinib ko\'ring.', 'danger');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }
    
    async deleteIshlanma(id) {
        if (!confirm('Ushbu ishlanmani o\'chirishga ishonchingiz komilmi? Bu amalni orqaga qaytarib bo\'lmaydi.')) return;
        
        const formData = new FormData();
        const csrfToken = document.getElementById('csrf-token-container').dataset.token;
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        const url = this.getRoute('delete');
        
        await this.performAction(url, formData);
    }
    
   async updateStatus(id, newStatus, reason = null) {
        const actionWord = newStatus === 'approved' ? 'tasdiqlashni' : 'rad etishni';
        if (newStatus === 'approved' && !confirm(`Ushbu ishlanmani ${actionWord} istaysizmi?`)) return;

        const formData = new FormData();
        const csrfToken = document.getElementById('csrf-token-container').dataset.token;
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        formData.append('status', newStatus);
        
        if (reason) {
            formData.append('reason', reason);
        }
        
        const url = this.getRoute('status');

        await this.performAction(url, formData);
    }

    async performAction(url, formData) {
        try {
            const response = await fetch(url, { method: 'POST', body: formData });
            if (!response.ok) {
                throw new Error(`Server xatosi: ${response.status} ${response.statusText}`);
            }
            const result = await response.json();
            showAlert(result.message, result.success ? 'success' : 'danger');
            
            if (result.success) {
                if (result.data && result.data.new_token) {
                    const tokenContainer = document.getElementById('csrf-token-container');
                    if (tokenContainer) {
                        tokenContainer.dataset.token = result.data.new_token;
                    }
                }

                if (result.data && result.data.pending_counts) {
                    const pendingCounts = result.data.pending_counts;
                    document.querySelectorAll('#ishlanma-tabs .nav-link').forEach(tabLink => {
                        const sectionCode = tabLink.dataset.section;
                        let badge = tabLink.querySelector('.badge');

                        if (pendingCounts[sectionCode] > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'badge bg-warning text-dark rounded-pill';
                                tabLink.appendChild(badge);
                            }
                            badge.textContent = pendingCounts[sectionCode];
                        } else {
                            if (badge) {
                                badge.remove();
                            }
                        }
                    });
                }
                
                if (typeof window.refreshTable === 'function') {
                    window.refreshTable();
                } else {
                    location.reload();
                }
            }
        } catch (error) {
            console.error(`Amalni bajarishda xatolik:`, error);
            showAlert('Server bilan bog\'lanishda xatolik.', 'danger');
        }
    }
}