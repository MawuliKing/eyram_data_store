document.addEventListener('DOMContentLoaded', function() {

    // --- Guard to prevent this script from running more than once ---
    if (window.isAgentScriptLoaded) {
        return;
    }
    window.isAgentScriptLoaded = true;

    // --- Global Variables & Definitions ---
    const BASE_URL_JS = '/digital_agent/'; // Use a fixed path for reliability in a .js file

    const buyModalEl = document.getElementById('buyModal');
    const buyModal = buyModalEl ? new bootstrap.Modal(buyModalEl) : null;
    const addToCartForm = document.getElementById('addToCartForm');

    const buyCheckerModalEl = document.getElementById('buyCheckerModal');
    const buyCheckerModal = buyCheckerModalEl ? new bootstrap.Modal(buyCheckerModalEl) : null;
    const addCheckerToCartForm = document.getElementById('addCheckerToCartForm');

    // --- Initial Cart Load ---
    fetch(`${BASE_URL_JS}agent/cart_handler.php?action=get`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                updateCartDisplay(data.cart);
            }
        });

    // --- Global Click Listener for Modal Triggers ---
    document.body.addEventListener('click', function(e) {
        const buyNowBtn = e.target.closest('.buy-now-btn');
        
        if (buyNowBtn) {
            e.preventDefault();
            document.getElementById('modal-data-amount').innerText = buyNowBtn.dataset.serviceName;
            document.getElementById('modal-price').innerText = `GHS ${parseFloat(buyNowBtn.dataset.servicePrice).toFixed(2)}`;
            document.getElementById('modal-network-name').innerText = buyNowBtn.dataset.providerName;
            document.getElementById('modal-service-id').value = buyNowBtn.dataset.serviceId;
            document.getElementById('modal-provider-name').value = buyNowBtn.dataset.providerName;
            document.getElementById('modal-category').value = buyNowBtn.dataset.category;
            if (buyModal) buyModal.show();
        }
          // START OF NEW CODE: Handle Bulk Upload Form
    // =============================================
    // ... inside the document.body.addEventListener('submit', ...)
if (form.matches('#bulkUploadForm')) {
    e.preventDefault(); // <-- The crucial part: stop the normal submission
    
    const formData = new FormData(form);
    const submitButton = form.querySelector('button[type="submit"]');
    const modalInstance = bootstrap.Modal.getInstance(document.getElementById('uploadTemplateModal'));

    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading & Processing...';

    // Send the file to the handler using AJAX
    fetch('/digital_agent/agent/upload_bulk_orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // We use our global helper function to show the pop-up and update the display
        handleCartResponse(data, data.message); 
    })
    .catch(handleCartError)
    .finally(() => {
        // Reset the form and button
        submitButton.disabled = false;
        submitButton.innerHTML = 'Upload and Add to Cart';
        form.reset();
        if (modalInstance) modalInstance.hide();
    });
}
        const buyCheckerBtn = e.target.closest('.buy-checker-btn');
        if (buyCheckerBtn) {
            e.preventDefault();
            document.getElementById('buyCheckerModalLabel').innerText = `Purchase ${buyCheckerBtn.dataset.serviceName}`;
            document.getElementById('modal-checker-name').innerText = buyCheckerBtn.dataset.serviceName;
            document.getElementById('modal-checker-price').innerText = `GHS ${parseFloat(buyCheckerBtn.dataset.servicePrice).toFixed(2)}`;
            document.getElementById('modal-checker-service-id').value = buyCheckerBtn.dataset.serviceId;
            document.getElementById('modal-checker-category').value = buyCheckerBtn.dataset.category;
            if (buyCheckerModal) buyCheckerModal.show();
        }
        
    });

    // --- Form Submission Listeners ---
    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

            fetch('/digital_agent/agent/cart_handler.php?action=add', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => handleCartResponse(data, 'Item added to cart!'))
                .catch(handleCartError)
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Add To Cart';
                    addToCartForm.reset();
                    if (buyModal) buyModal.hide();
                });
        });
    }

    if (addCheckerToCartForm) {
        addCheckerToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            const formData = new FormData(this);
            formData.append('provider_name', 'Voucher Service');

            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

            fetch('/digital_agent/agent/cart_handler.php?action=add', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => handleCartResponse(data, 'Voucher added to cart!'))
                .catch(handleCartError)
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Add to Cart & Continue';
                    if (buyCheckerModal) buyCheckerModal.hide();
                    addCheckerToCartForm.reset();
                });
        });
    }

    // --- AFA Registration Form Submission ---
const afaForm = document.getElementById('addAfaToCartForm');
const afaModalEl = document.getElementById('afaFormModal');
const afaModal = afaModalEl ? new bootstrap.Modal(afaModalEl) : null;

if (afaForm) {
    afaForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

        let hasFiles = false;
        for (let [key, value] of formData.entries()) {
            if (value instanceof File && value.name) {
                hasFiles = true;
                break;
            }
        }

        const endpoint = hasFiles
            ? '/digital_agent/agent/cart_handler_form.php?action=add_with_files'
            : '/digital_agent/agent/cart_handler.php?action=add_form';

        // If no files, wrap specific fields in JSON (for add_form)
        if (!hasFiles) {
            const cartFormData = new FormData();
            cartFormData.append('service_id', formData.get('service_id'));
            cartFormData.append('provider_name', formData.get('provider_name'));
            cartFormData.append('category', formData.get('category'));

            const form_data_obj = {};
            for (let [key, value] of formData.entries()) {
                if (!['service_id', 'provider_name', 'category'].includes(key)) {
                    form_data_obj[key] = value;
                }
            }
            cartFormData.append('form_data', JSON.stringify(form_data_obj));

            fetch(endpoint, {
                method: 'POST',
                body: cartFormData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Form added to cart!', showConfirmButton: false, timer: 2000 });
                        updateCartDisplay(data.cart);
                        if (afaModal) afaModal.hide();
                        afaForm.reset();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
                    }
                })
                .catch(handleCartError)
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Submit and Add to Cart';
                });
        } else {
            // If files are present, send full formData directly
            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Form with files added to cart!', showConfirmButton: false, timer: 2000 });
                        updateCartDisplay(data.cart);
                        if (afaModal) afaModal.hide();
                        afaForm.reset();
                    } else {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
                    }
                })
                .catch(handleCartError)
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Submit and Add to Cart';
                });
        }
    });
}
// ... (The entire, working AFA Registration Form Submission block ends here)


// =================================================================
// START OF NEW CODE: BUSINESS CERTIFICATE FORM SUBMISSION
// This is a direct replication of the AFA form logic.
// =================================================================
const bizCertForm = document.getElementById('addBizCertToCartForm');
const bizCertModalEl = document.getElementById('bizCertFormModal');
const bizCertModal = bizCertModalEl ? new bootstrap.Modal(bizCertModalEl) : null;

if (bizCertForm) {
    bizCertForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

        // The Business Certificate form ALWAYS has file uploads, so we can simplify.
        // We will always send it to the dedicated file handler.
        const endpoint = '/digital_agent/agent/cart_handler_form.php?action=add_with_files';
        
        // Send the full formData directly, as it contains the files.
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // We use the global helper function for a consistent response.
            handleCartResponse(data, 'Business Certificate Application added to cart!');
        })
        .catch(handleCartError)
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit and Add to Cart';
            if (bizCertModal) bizCertModal.hide();
            bizCertForm.reset();
        });
    });
}
// ... (The TIN Registration form listener block ends here) ...


// =================================================================
// START OF NEW CODE: AGENT SIM REGISTRATION FORM SUBMISSION
// =================================================================
const agentSimForm = document.getElementById('addAgentSimToCartForm');
const agentSimModalEl = document.getElementById('agentSimFormModal');
const agentSimModal = agentSimModalEl ? new bootstrap.Modal(agentSimModalEl) : null;

if (agentSimForm) {
    agentSimForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

        // This form has file uploads, so we send it to the dedicated file handler.
        const endpoint = '/digital_agent/agent/cart_handler_form.php?action=add_with_files';
        
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            handleCartResponse(data, 'Agent SIM Registration added to cart!');
        })
        .catch(handleCartError)
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit and Add to Cart';
            if (agentSimModal) agentSimModal.hide();
            agentSimForm.reset();
        });
    });
}
// =================================================================
// END OF NEW CODE
// =================================================================


// ... (The rest of your global script continues here) ...
// =================================================================
// END OF NEW CODE
// =================================================================


// ... (The rest of your global script, like the cart button listeners and helper functions, follows here) ...
// ... (The entire, working AFA Registration Form Submission block ends here) ...


// =======================================================
// START OF NEW CODE: TIN REGISTRATION FORM SUBMISSION
// This is a direct copy of the AFA logic, adapted for the TIN form.
// =======================================================
const tinForm = document.getElementById('addTinToCartForm');
const tinModalEl = document.getElementById('tinFormModal');
const tinModal = tinModalEl ? new bootstrap.Modal(tinModalEl) : null;

if (tinForm) {
    tinForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

        // Since the TIN form ALWAYS has file uploads, we can directly point to the file handler.
        const endpoint = '/digital_agent/agent/cart_handler_form.php?action=add_with_files';
        
        // We send the full formData directly, as it contains files.
        fetch(endpoint, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // The global handleCartResponse function will take care of the pop-up and display update.
            handleCartResponse(data, 'TIN Registration added to cart!');
        })
        .catch(handleCartError)
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Submit and Add to Cart';
            if (tinModal) tinModal.hide();
            tinForm.reset();
        });
    });
}
// =======================================================
// END OF NEW CODE
// =======================================================


// ... (The rest of your global script, like the cart button listeners and helper functions, follows here) ...

    // --- Global click listener for cart actions (Remove, Clear, Process) ---
    document.body.addEventListener('click', function(e) {
        const removeBtn = e.target.closest('.remove-from-cart-btn');
        if (removeBtn) {
            e.preventDefault();
            const cartKey = removeBtn.dataset.cartKey;
            fetch(`/digital_agent/agent/cart_handler.php?action=remove&key=${cartKey}`)
                .then(response => response.json())
                .then(data => handleCartResponse(data, 'Item removed.'))
                .catch(handleCartError);
        }

        const clearBtn = e.target.closest('#clear-cart-btn');
        if (clearBtn) {
            e.preventDefault();
            fetch('/digital_agent/agent/cart_handler.php?action=clear')
                .then(response => response.json())
                .then(data => handleCartResponse(data, 'Cart has been cleared.'))
                .catch(handleCartError);
        }

        const processBtn = e.target.closest('#process-order-btn');
        if (processBtn) {
            e.preventDefault();
            processBtn.disabled = true;
            processBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

            fetch('/digital_agent/agent/cart_handler.php?action=process')
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({ icon: 'success', title: 'Success!', text: data.message })
                            .then(() => { window.location.href = data.redirect_url; });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
                    }
                })
                .catch(handleCartError)
                .finally(() => {
                    processBtn.disabled = false;
                    processBtn.innerHTML = '<i class="fas fa-arrow-circle-right me-2"></i>Process Orders';
                });
        }
    });

    // --- Global Helper Functions ---
    function handleCartResponse(data, successMessage) {
        if (data.status === 'success') {
            updateCartDisplay(data.cart);
            if (successMessage) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: successMessage, showConfirmButton: false, timer: 2000 });
            }
        } else {
            Swal.fire({ icon: 'error', title: 'Oops...', text: data.message });
        }
    }

    function handleCartError(error) {
        Swal.fire({ icon: 'error', title: 'Error', text: 'Could not connect to the server.' });
        console.error('Error:', error);
    }

    function updateCartDisplay(cartData) {
        const cartCountBadge = document.getElementById('cart-count-badge');
        const cartItemsContainer = document.getElementById('cart-items-container');
        const cartTotalEl = document.getElementById('cart-total');
        const processOrderBtn = document.getElementById('process-order-btn');

        if (!cartCountBadge || !cartItemsContainer || !cartTotalEl || !processOrderBtn) return;

        cartCountBadge.innerText = cartData.item_count > 0 ? cartData.item_count : '';

        let itemsHtml = '';
        if (cartData.item_count > 0) {
            cartData.items.forEach(item => {
                itemsHtml += `<div class="card shadow-sm mb-3"><div class="card-body p-3"><div class="d-flex justify-content-between align-items-start"><div><span class="fw-bold text-uppercase" style="color: #f39c12;">${item.provider_name}</span><small class="text-muted mx-1">•</small><small class="text-muted">Non Expiry</small></div><a href="#" class="text-danger remove-from-cart-btn" data-cart-key="${item.cart_key}" title="Remove Item"><i class="fas fa-trash-alt"></i></a></div><hr class="my-2"><div class="d-flex justify-content-between align-items-center"><div><h4 class="fw-bold mb-0">${item.name}</h4><div class="d-flex align-items-center text-muted mt-1"><i class="fas fa-user me-2"></i><span>${item.recipient_phone || item.form_data?.fullName || 'N/A'}</span></div></div><div class="text-end"><small class="text-muted">Price</small><h5 class="fw-bold text-success mb-0">GHS ${parseFloat(item.price).toFixed(2)}</h5></div></div></div></div>`;
            });
            cartItemsContainer.innerHTML = itemsHtml;
            processOrderBtn.disabled = false;
        } else {
            cartItemsContainer.innerHTML = '<div class="text-center p-5 text-muted">Your cart is empty.</div>';
            processOrderBtn.disabled = true;
        }
        cartTotalEl.innerText = `GHS ${cartData.total_price.toFixed(2)}`;
    }
});
