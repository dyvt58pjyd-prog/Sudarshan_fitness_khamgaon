function generateStaffQR() {
    var paymentMode = "UPI"; // mock
    var qrContainer = { style: { display: 'none' } }; // mock
    
    if (paymentMode !== 'UPI') {
        qrContainer.style.display = 'none';
        return;
    }
    
    var planSelect = { options: [ { getAttribute: () => "12000" } ], selectedIndex: 0 }; // mock
    var planPrice = 0;
    if (planSelect && planSelect.options[planSelect.selectedIndex]) {
        var selectedOpt = planSelect.options[planSelect.selectedIndex];
        if (selectedOpt.getAttribute('data-price')) {
            planPrice = parseFloat(selectedOpt.getAttribute('data-price'));
        }
    }
    
    var discount = 0; // mock
    var ptFees = 0; // mock
    var trainerSelect = { value: '' }; // mock
    if (trainerSelect && trainerSelect.value !== '') {
        var ptDuration = { value: '3' };
        if (ptDuration) {
            var duration = parseInt(ptDuration.value) || 3;
            const pt_rates = { 1: 3000, 2: 6000, 3: 9000, 6: 18000, 12: 35000 };
            ptFees = pt_rates[duration] || (duration * 3000);
        }
    }
    
    var totalAmount = (planPrice - discount) + ptFees;
    if (totalAmount < 0) totalAmount = 0;
    
    console.log("Total: " + totalAmount);
    // document.getElementById('staff-qr-amount').innerText = '₹' + totalAmount.toLocaleString('en-IN');
    
    var upiId = "user@upi";
    var gymName = "Gym";
    
    if (!upiId) {
        // document.getElementById('staff-qr-container').innerHTML = '<div style="color:red; padding: 10px;">UPI ID not configured in settings.</div>';
        qrContainer.style.display = 'block';
        return;
    }
    
    var cleanUpiId = upiId.replace(/\s+/g, '');
    var queryStr = `?pa=${cleanUpiId}&pn=${encodeURIComponent(gymName)}&am=${totalAmount.toFixed(2)}&tn=${encodeURIComponent('Registration Payment')}&cu=INR`;
    var upiUrl = `upi://pay${queryStr}`;
    
    var qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(upiUrl);
    // document.getElementById('staff-qr-code').src = qrSrc;
    
    qrContainer.style.display = 'block';
}

generateStaffQR();
