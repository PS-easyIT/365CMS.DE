(function () {
    'use strict';

    function submitFormWithTemporarySubmitter(form) {
        if (!form) {
            return;
        }

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
            return;
        }

        if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
            return;
        }

        const fallbackButton = document.createElement('button');
        fallbackButton.type = 'submit';
        fallbackButton.hidden = true;
        fallbackButton.tabIndex = -1;
        fallbackButton.setAttribute('aria-hidden', 'true');
        form.appendChild(fallbackButton);
        fallbackButton.click();
        fallbackButton.remove();
    }

    function bindSubmitLock(form) {
        if (!form || form.dataset.subscriptionSubmitBound === '1') {
            return;
        }

        form.dataset.subscriptionSubmitBound = '1';
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitLocked === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.submitLocked = '1';
        });
    }

    function resetSubmitLock(form) {
        if (form) {
            form.dataset.submitLocked = '0';
        }
    }

    function parseJsonAttribute(value) {
        if (typeof value !== 'string' || value.trim() === '') {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (error) {
            return null;
        }
    }

    function initSubmitLocks() {
        document.querySelectorAll('form[data-subscription-submit-lock="1"]').forEach(function (form) {
            bindSubmitLock(form);
        });
    }

    function initPackageAdmin() {
        const packageModalElement = document.getElementById('packageModal');
        const packageForm = document.getElementById('packageForm');
        const packageModalTitle = document.getElementById('packageModalTitle');
        const packageId = document.getElementById('pkg-id');
        const packageName = document.getElementById('pkg-name');
        const packageSlug = document.getElementById('pkg-slug');
        const packagePriceMonthly = document.getElementById('pkg-price_monthly');
        const packagePriceYearly = document.getElementById('pkg-price_yearly');
        const packageDescription = document.getElementById('pkg-description');
        const packageLimitExperts = document.getElementById('pkg-limit_experts');
        const packageLimitCompanies = document.getElementById('pkg-limit_companies');
        const packageLimitEvents = document.getElementById('pkg-limit_events');
        const packageLimitSpeakers = document.getElementById('pkg-limit_speakers');
        const packageLimitStorage = document.getElementById('pkg-limit_storage_mb');
        const packageSortOrder = document.getElementById('pkg-sort_order');
        const packageIsActive = document.getElementById('pkg-is_active');
        const packageIsFeatured = document.getElementById('pkg-is_featured');

        if (!packageModalElement || !packageForm) {
            return;
        }

        bindSubmitLock(packageForm);

        const resetPackageForm = function () {
            resetSubmitLock(packageForm);

            if (packageModalTitle) {
                packageModalTitle.textContent = 'Neues Paket';
            }
            if (packageId) {
                packageId.value = '0';
            }
            if (packageName) {
                packageName.value = '';
            }
            if (packageSlug) {
                packageSlug.value = '';
            }
            if (packagePriceMonthly) {
                packagePriceMonthly.value = '';
            }
            if (packagePriceYearly) {
                packagePriceYearly.value = '';
            }
            if (packageDescription) {
                packageDescription.value = '';
            }
            if (packageLimitExperts) {
                packageLimitExperts.value = '-1';
            }
            if (packageLimitCompanies) {
                packageLimitCompanies.value = '-1';
            }
            if (packageLimitEvents) {
                packageLimitEvents.value = '-1';
            }
            if (packageLimitSpeakers) {
                packageLimitSpeakers.value = '-1';
            }
            if (packageLimitStorage) {
                packageLimitStorage.value = '1000';
            }
            if (packageSortOrder) {
                packageSortOrder.value = '0';
            }
            if (packageIsActive) {
                packageIsActive.checked = true;
            }
            if (packageIsFeatured) {
                packageIsFeatured.checked = false;
            }

            ['experts', 'companies', 'events', 'speakers'].forEach(function (field) {
                const checkbox = document.getElementById('pkg-plugin_' + field);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

            ['analytics', 'advanced_search', 'api_access', 'custom_branding', 'priority_support', 'export_data', 'integrations', 'custom_domains'].forEach(function (field) {
                const checkbox = document.getElementById('pkg-feature_' + field);
                if (checkbox) {
                    checkbox.checked = false;
                }
            });
        };

        const openPackageModal = function () {
            if (typeof bootstrap === 'undefined') {
                return;
            }

            bootstrap.Modal.getOrCreateInstance(packageModalElement).show();
        };

        document.querySelectorAll('[data-package-create]').forEach(function (button) {
            button.addEventListener('click', function () {
                resetPackageForm();
            });
        });

        document.querySelectorAll('[data-package-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                const pkg = parseJsonAttribute(button.getAttribute('data-package-edit'));
                if (!pkg) {
                    return;
                }

                resetSubmitLock(packageForm);

                if (packageModalTitle) {
                    packageModalTitle.textContent = 'Paket bearbeiten';
                }
                if (packageId) {
                    packageId.value = String(pkg.id || 0);
                }
                if (packageName) {
                    packageName.value = pkg.name || '';
                }
                if (packageSlug) {
                    packageSlug.value = pkg.slug || '';
                }
                if (packagePriceMonthly) {
                    packagePriceMonthly.value = pkg.price_monthly ?? pkg.price ?? '';
                }
                if (packagePriceYearly) {
                    packagePriceYearly.value = pkg.price_yearly ?? ((pkg.price ?? null) !== null ? Number(pkg.price || 0) * 12 : '');
                }
                if (packageDescription) {
                    packageDescription.value = pkg.description || '';
                }
                if (packageLimitExperts) {
                    packageLimitExperts.value = pkg.limit_experts ?? -1;
                }
                if (packageLimitCompanies) {
                    packageLimitCompanies.value = pkg.limit_companies ?? -1;
                }
                if (packageLimitEvents) {
                    packageLimitEvents.value = pkg.limit_events ?? -1;
                }
                if (packageLimitSpeakers) {
                    packageLimitSpeakers.value = pkg.limit_speakers ?? -1;
                }
                if (packageLimitStorage) {
                    packageLimitStorage.value = pkg.limit_storage_mb ?? 1000;
                }
                if (packageSortOrder) {
                    packageSortOrder.value = pkg.sort_order || 0;
                }
                if (packageIsActive) {
                    packageIsActive.checked = !!parseInt(pkg.is_active, 10);
                }
                if (packageIsFeatured) {
                    packageIsFeatured.checked = !!parseInt(pkg.is_featured, 10);
                }

                ['experts', 'companies', 'events', 'speakers'].forEach(function (field) {
                    const checkbox = document.getElementById('pkg-plugin_' + field);
                    if (checkbox) {
                        checkbox.checked = !!parseInt(pkg['plugin_' + field], 10);
                    }
                });

                ['analytics', 'advanced_search', 'api_access', 'custom_branding', 'priority_support', 'export_data', 'integrations', 'custom_domains'].forEach(function (field) {
                    const checkbox = document.getElementById('pkg-feature_' + field);
                    if (checkbox) {
                        checkbox.checked = !!parseInt(pkg['feature_' + field], 10);
                    }
                });

                openPackageModal();
            });
        });

        document.querySelectorAll('.js-package-delete').forEach(function (button) {
            button.addEventListener('click', function () {
                const formId = button.getAttribute('data-delete-form-id') || '';
                const packageNameLabel = button.getAttribute('data-package-name') || 'Paket';
                const targetForm = formId !== '' ? document.getElementById(formId) : null;

                if (!targetForm) {
                    return;
                }

                const confirmDelete = function () {
                    submitFormWithTemporarySubmitter(targetForm);
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Paket löschen?',
                        message: 'Dieses Paket („' + packageNameLabel + '“) wird unwiderruflich gelöscht.',
                        confirmText: 'Löschen',
                        confirmClass: 'btn-danger',
                        onConfirm: confirmDelete,
                    });
                    return;
                }

                if (window.confirm('Dieses Paket („' + packageNameLabel + '“) wird unwiderruflich gelöscht.')) {
                    confirmDelete();
                }
            });
        });

        packageModalElement.addEventListener('hidden.bs.modal', function () {
            resetSubmitLock(packageForm);
        });
    }

    function initOrderAdmin() {
        const assignUser = document.getElementById('assign-user');
        const assignPlan = document.getElementById('assign-plan');
        const assignCycle = document.getElementById('assign-cycle');
        const assignModalElement = document.getElementById('assignModal');
        const assignForm = document.getElementById('assignForm');

        if (!assignModalElement || !assignForm) {
            return;
        }

        bindSubmitLock(assignForm);

        const resetAssignForm = function () {
            if (assignUser) {
                assignUser.value = '';
            }
            if (assignPlan) {
                assignPlan.value = '';
            }
            if (assignCycle) {
                assignCycle.value = 'monthly';
            }

            resetSubmitLock(assignForm);
        };

        const openAssignFromOrder = function (order) {
            resetAssignForm();

            if (assignUser && order.user_id) {
                assignUser.value = String(order.user_id);
            }

            const linkedPlanId = order.plan_id || order.package_id || order.linked_plan_id || '';
            if (assignPlan && linkedPlanId) {
                assignPlan.value = String(linkedPlanId);
            }

            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(assignModalElement).show();
            }
        };

        document.querySelectorAll('[data-assign-reset="true"]').forEach(function (button) {
            button.addEventListener('click', function () {
                resetAssignForm();
            });
        });

        document.querySelectorAll('[data-assign-order]').forEach(function (button) {
            button.addEventListener('click', function () {
                const order = parseJsonAttribute(button.getAttribute('data-assign-order'));
                if (!order) {
                    resetAssignForm();
                    return;
                }

                openAssignFromOrder(order);
            });
        });

        document.querySelectorAll('[data-delete-order-form]').forEach(function (button) {
            button.addEventListener('click', function () {
                const formId = button.getAttribute('data-delete-order-form') || '';
                const orderNumber = button.getAttribute('data-delete-order-number') || '#';
                const targetForm = formId !== '' ? document.getElementById(formId) : null;

                if (!targetForm) {
                    return;
                }

                const confirmDelete = function () {
                    submitFormWithTemporarySubmitter(targetForm);
                };

                if (typeof cmsConfirm === 'function') {
                    cmsConfirm({
                        title: 'Bestellung löschen?',
                        message: orderNumber,
                        onConfirm: confirmDelete,
                    });
                    return;
                }

                if (window.confirm('Bestellung ' + orderNumber + ' wirklich löschen?')) {
                    confirmDelete();
                }
            });
        });

        assignModalElement.addEventListener('hidden.bs.modal', function () {
            resetSubmitLock(assignForm);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initSubmitLocks();
        initPackageAdmin();
        initOrderAdmin();
    });
}());
