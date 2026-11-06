document.addEventListener('DOMContentLoaded', () => {
    const months = document.getElementById('numMhnes');
    const weeklyHours = document.getElementById('wresPerWeek');
    const monthlyPay = document.getElementById('payPerMonth');
    const totalPay = document.getElementById('payTotal');

    if (!months || !weeklyHours || !monthlyPay || !totalPay) {
        return;
    }

    const calculateTotal = () => {
        monthlyPay.value = weeklyHours.value === '20' ? '175.00' : weeklyHours.value === '40' ? '350.00' : '';
        const value = Number.parseFloat(months.value) * Number.parseFloat(monthlyPay.value);
        totalPay.value = Number.isFinite(value) ? value.toFixed(2) : '';
    };

    months.addEventListener('input', calculateTotal);
    weeklyHours.addEventListener('change', calculateTotal);
    calculateTotal();
});
