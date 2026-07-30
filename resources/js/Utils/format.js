export const formatPhone = (val) => {
    if (!val) return '';

    // If typing just started, make sure +62 is there
    if (val === '+62' || val === '+' || val === '+6') return '+62-';

    // Extract digits only
    let digits = val.replace(/\D/g, '');

    // Normalize prefix
    if (digits.startsWith('62')) {
        digits = digits.substring(2);
    } else if (digits.startsWith('0')) {
        digits = digits.substring(1);
    }

    if (digits.length === 0) return '+62-';

    // Format as +62-8xx-xxxx-xxxx
    let res = '+62';
    if (digits.length > 0) {
        res += '-' + digits.substring(0, 3);
    }
    if (digits.length > 3) {
        res += '-' + digits.substring(3, 7);
    }
    if (digits.length > 7) {
        res += '-' + digits.substring(7, 13); // up to 13 digits is enough
    }
    return res;
};
